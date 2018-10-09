<?php

use Robo\Robo;

/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see     http://robo.li/
 *
 * @warning This script is not yet ready for public consumption!  See @todos.
 */
class RoboFile extends \Robo\Tasks {

  // define public methods as commands
  function hello($world) {
    $this->say("Hello $world");
  }

  /**
   * Alias function for new_ticket()
   *
   * @inheritdoc new_ticket()
   */
  function nt() {
    $this->taskExec($this->new_ticket());
  }

    /**
     * Resets your local dev environment to start a new task.
     *
     * You can control the following variables in robo.yml:
     *
     * @var string $upstream_repo Name of the upstream repository
     * @var string $fork_repo Name of your forked repository
     * @var string $base_branch Name of the master branch.  Usually "master".
     * @var string $new_branch Name of your feature branch.
     * @var string $runner Task runner / package manager, eg. Composer.
     * @var string $vm_start Command to start the virtual machine
     *
     * Credits:
     *
     * @see  https://git.businesswire.com/projects/HQ/repos/hq-tools/browse/dev/reset-dev
     *       With gratitude to Ben Thornton
     * @see  https://github.com/g1a/starter
     *      And hat tip to G1A
     *
     * @TODO add an option to start from master and create the feature branch
     * @TODO add a wizard to provide config if it doesn't already exist
     * @TODO use vm_commands from yaml file
     *
     * @throws \Robo\Exception\TaskException
     */
  function new_ticket() {
    $this->say("Hi!  I'm going to help you refresh your local dev environment to start a new ticket.");

    // Load config & set environment variables
    $start_time = time();
    $allopts = Robo::config()->get("command.new_ticket.options");
    foreach ($allopts as $key => $value) {
      $$key = $value;
    }
    $new_branch = exec("cd $host_path; git symbolic-ref --short HEAD");
    $ssh_commands = $this->taskExecStack();

    // Description of tasks to be performed, repeats at both the beginning and end of the script.
    $tasks = [
      "Start in $host_path",
      "Pull a fresh copy of $upstream_repo/$base_branch",
      "Push to $fork_repo/$base_branch",
      "Reset $new_branch to match the latest $upstream_repo/$base_branch",
      "Push to $fork_repo/$new_branch and set upstream",
      "Task runner: $runner",
      "Turn on the virtual machine: $vm_start",
      "SSH to VM using private key: $vm_key",
    ];

    // Add descriptions of tasks to be performed *inside* the VM
    // Also set up commands to be run
    $command_tasks = "Run commands inside the VM:\n";
    $indent = "   - ";
    foreach ($vm_commands as $key => $value) {
        $command_tasks .= $indent . $key . "\n";
        $ssh_commands->exec($value);
    }
    $tasks[] = $command_tasks;

    $requirements = [
      "You have forked the 'upstream' repository and created your own",
      "You've already created a feature branch for the new ticket",
      "You've updated the enclosed config file with the correct repositories and branches",
    ];

    // @TODO: make more steps optional
    $this->io()->text("Here's what I can do:");
    $this->io()->listing($tasks);
    $this->io()->text("In order for this to work, please make sure:");
    $this->io()->listing($requirements);
    $continue = $this->confirm("CONTINUE?");

    if (!$continue) {
        $this->say("Operation has been canceled.");
        return;
    }

    // actual steps go here
    // @TODO: make sure you start with a clean copy of master every time
    // @SEE: https://stackoverflow.com/questions/5288172/git-replace-local-version-with-remote-version
    $result = $this->taskGitStack()
      ->stopOnFail()
      ->dir($host_path)
      ->checkout("-B $base_branch $upstream_repo/$base_branch")
      ->pull($upstream_repo, $base_branch)
      ->push($fork_repo, $base_branch)
      ->exec("git branch -D $new_branch")
      ->checkout("-B $new_branch $upstream_repo/$base_branch")
      ->exec("git push $fork_repo $new_branch --set-upstream")
      ->run();
    $this->taskExec($this->check_success($result, "Set up git"));

    // See if there's anything new to install from Composer.
    $this->say("I'm going to see if there's anything to install.");
    $result = $this->taskComposerInstall()
        ->dir($host_path)
        ->dev()
        ->run();
    $this->taskExec($this->check_success($result, "Composer install"));

    // Turn on the VM and reprovision it if necessary
    $this->say("Let's turn this thing on.");
    $result = $this->taskExecStack()
        ->stopOnFail()
        ->dir($host_path)
        ->exec($vm_start)
        ->run();
    $this->taskExec($this->check_success($result, $vm_start));

    // Run tasks inside the VM
    $this->say("I'm going to run some commands inside the VM now.");
    $result = $this->taskSshExec($vm_domain, $vm_user)
      ->stopOnFail()
      ->port($vm_port)
      ->identityFile($vm_key)
      ->remoteDir($guest_path)
      ->dir($host_path)
      ->exec($ssh_commands)
      ->run();
    $this->taskExec($this->check_success($result, "Commands inside the VM"));

    if (!$result->wasSuccessful()) {
        $this->io()->error("Sorry, I was not able to finish setup.");
        return 1;
    }

    // Outro
    $this->say("Congratulations, we're done!  Here's what we did:");
    $this->io()->listing($tasks);
    $stop_time = time();
    $elapsed_time = $stop_time - $start_time;
    $elapsed_minutes = floor($elapsed_time / 60);
    $elapsed_seconds = $elapsed_time - $elapsed_minutes * 60;
    $this->say("This took $elapsed_minutes minutes and $elapsed_seconds seconds.");
    $this->io()->success("Now go forth and be awesome.");
  }

  /**
   * Update contrib code on all your Drupal sites at once.
   *
   * Loops through all sites defined in robo.yml.  Executes all commands found
   * in the yaml file.
   *
   * @param string $path Specify the base path for your webroot.
   */
  function updateme($path = "/var/www/d7/sites") {
    $this->io()->title("UPDATE ALL THE THINGS!!!");

    // Load sites, commands, and path to webroot
    $opts = Robo::config()->get("command.updateme.options");
    $sites = $opts["sites"];
    $commands = $opts["commands"];
    $this->io()->text("I'm going to update these sites:");
    $this->io()->listing($sites);
    $this->io()->text("And this is what I'll do:");
    $this->io()->listing($commands);
    $this->ask("Press Enter to continue, or ctrl-c to cancel.");

    foreach ($sites as $site) {
      $this->io()->section($site);

      // Run commands in sequence.
      foreach ($commands as $key => $value) {
        $this->say($key);
        $this->taskExec("cd $path/$site; $value")->run();
      }
    }

    $this->io()
      ->success("All done!  Pat yourself on the back for a job well done.");
  }

  /**
   * Reusable function to report whether the previous step succeeded.
   *
   * @param object $result
   *   Pass the result object to this function.
   * @param string $task
   *   Plain text description of the currently running task.
   * @return int
   *   Should correctly report whether task was successful or not.
   */
  function check_success($result = null, $task = "Current task") {
  if (!$result) {
        $message = $this->io()->error("Sorry, something went wrong with $task");
        exit($message);
  } elseif ($result->wasSuccessful()) {
        $this->io()->success("$task was successful!");
        return 0;
  } else {
      $message = $this->io()->error("Sorry, something went wrong with $task");
      exit($message);
  }
  }

    /**
     * Install all your favorite things on a new environment.
     * @TODO automatically detect environment
     *
     * @param string $os
     *   If you specify an operating system, I'll install extra goodies.
     * @throws \Robo\Exception\TaskException
     */
    function new_environment($os = null) {
        if ($os == 'mac') {
            // install Homebrew
            $result = $this->taskExecStack()
                ->exec('/usr/bin/ruby -e "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/master/install)"')
                ->run();
            $this->check_success($result, 'Installing Homebrew');

            // install apps using Homebrew
            $brews = [
                'ansible',
                'composer',
                'cowsay',
                'curl',
                'docker',
                'figlet',
                'fortune',
                'git',
                'glib',
                'hub',
                'lolcat',
                'node',
                'nvm',
                'php@7.1',
                'ssh-copy-id',
                'wget',
            ];
            $casks = [
                '1clipboard',
                'beardedspice',
                'chromedriver',
                'google-chrome',
                'firefox',
                'font-source-code-pro',
                'java',
                'lando',
                'livereload',
                'phpstorm',
                'slack',
                'spectacle',
                'spotify',
                'vagrant',
                'virtualbox',
                'virtualbox-extension-pack',
            ];
            $repos = [
                'brew install' => $brews,
                'brew cask install' => $casks,
            ];

            // Loop through both arrays
            foreach ($repos as $command => $desires) {
                foreach ($desires as $desire) {
                    $this->taskExecStack()
                        ->exec($command . " " . $desire)
                        ->run();
                }
            }
        } else {
            $this->io()->note("If you specify an operating system, I can install a lot more stuff.");
        }

        // Install .bash_profile and other items consistent with all Linux & Unix environments
        $files = [
            '.bash_profile' => 'bash profile: better command line prompt & command aliases',
            '.bash_logout' => 'cute farewell greeting when you log out',
            '.vimrc' => 'better VI settings',
        ];
        foreach ($files as $file => $description) {
            $this->say("Installing " . $description);
            $this->taskFilesystemStack()
                ->copy($file, "~/$file")
                ->run();
        }

        // Outro
        $this->taskExecStack()
            ->exec("figlet All Done! | lolcat")
            ->run();
    }
}