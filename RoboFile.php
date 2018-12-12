<?php

use Robo\Robo;

/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see     http://robo.li/
 *
 * @warning This tool is still in beta!  See @todos.
 */
class RoboFile extends \Robo\Tasks {

  /**
   * Alias function for new_ticket()
   *
   * Shortcut for new_ticket().
   *
   * @option $no-reset Don't load the latest version of master into your
   * feature branch
   * @option $no-provision Don't rebuild the virtual machine, just run
   * `vagrant up`
   * @option $no-install Don't run `blt setup` inside the VM, but still
   * import config, update database, and log in as admin
   *
   * @inheritdoc new_ticket()
   */
  function nt($opts = [
    'no-reset' => FALSE,
    'no-provision' => FALSE,
    'no-install' => FALSE,
  ]) {
    $this->taskExec($this->new_ticket($opts));
  }

  /**
   * Resets your local dev environment to start a new task.
   *
   * You can set your own variables in robo.yml.
   *
   * @option $no-reset Don't load the latest version of master into your
   * feature branch
   * @option $no-provision Don't rebuild the virtual machine, just run
   * `vagrant up`
   * @option $no-install Don't run `blt setup` inside the VM, but still
   * import config, update database, and log in as admin
   *
   * @see  https://git.businesswire.com/projects/HQ/repos/hq-tools/browse/dev/reset-dev
   *   With gratitude to Ben Thornton
   * @see  https://github.com/g1a/starter
   *   And hat tip to G1A
   *
   * @TODO add an option to start from master and create the feature branch
   * @TODO add a wizard to provide config if it doesn't already exist
   *
   * @param array $opts
   *
   * @return int
   * @throws \Robo\Exception\TaskException
   */
  function new_ticket($opts = [
    'no-reset' => FALSE,
    'no-provision' => FALSE,
    'no-install' => FALSE,
  ]) {
    // Load config & set environment variables
    $start_time = time();
    $allopts = Robo::config()->get("command.new_ticket.options");
    foreach ($allopts as $key => $value) {
      $$key = $value;
    }
    $new_branch = exec("cd $host_path; git symbolic-ref --short HEAD");

    // Preparation commands to be run before everything else.
    $this->say("Shaping the environment . . .");
    foreach ($setup_commands as $each) {
      exec($each);
    }

    // Description of tasks to be performed, repeats at both the beginning and end of the script.
    // Assemble the tasks array.  Check for command line arguments before adding git reset or vagrant provision.
    $tasks = [];
    $tasks += ["Start in $host_path",];

    if (!$opts['no-reset']) {
      $tasks[] = "Pull a fresh copy of " . exec("tput setaf 6; echo '$upstream_repo/$base_branch'") . exec("tput sgr0");
      $tasks[] = "Push to " . exec("tput setaf 6; echo '$fork_repo/$base_branch'") . exec("tput sgr0");
      $tasks[] = "Reset " . exec("tput setaf 6; echo '$new_branch'") . exec("tput sgr0") . " to match the latest " . exec("tput setaf 6; echo '$upstream_repo/$base_branch'") . exec("tput sgr0");
      $tasks[] = "Push to " . exec("tput setaf 6; echo '$fork_repo/$new_branch'") . exec("tput sgr0") . " and set upstream " . exec("tput sgr0");
//      $tasks += [
//        "Pull a fresh copy of $upstream_repo/$base_branch",
//        "Push to $fork_repo/$base_branch",
//        "Reset $new_branch to match the latest $upstream_repo/$base_branch",
//        "Push to $fork_repo/$new_branch and set upstream",
//      ];
    }

    $tasks[] = "Task runner: $runner";

    if (!$opts['no-provision']) {
      $tasks[] = "Turn on the virtual machine: $vm_start";
    }

    $tasks[] = "SSH to VM using private key: $vm_key";

    // Add descriptions of tasks to be performed *inside* the VM
    // Also set up commands to be run
    // Also checks for --no-reset flag, and skips BLT setup.
    $ssh_commands = $this->taskExecStack();
    $command_tasks = "Run commands inside the VM:\n";
    $indent = "   - ";

    foreach ($vm_commands as $key => $value) {
      if ($key != 'BLT setup' or !$opts['no-install']) {
        $command_tasks .= $indent . $key . "\n";
        $ssh_commands->exec($value);
      }
    }

    $tasks[] = $command_tasks;
    $warning = exec('tput setaf 1; echo "CISCO AMP HAS BEEN TURNED OFF"');
    exec('tput setaf 9');

    // Add requirements to the end of the to-do list.
    $tasks[] = "In order for this to work, please make sure:\n" .
      $indent . "You have forked the 'upstream' repository and created your own" . "\n" .
      $indent . "You've already created a feature branch for the new ticket" . "\n" .
      $indent . "You've updated the enclosed config file with the correct repositories and branches" . "\n" .
      $indent . $warning . "\n";

    $this->intro("NEW TICKET",
      "I'm going to help you refresh your local dev environment to start a new ticket.",
      $tasks);

    // Don't reset the feature branch if I pass a tag on the command line.
    if (!$opts['no-reset']) {
      $this->taskExec($this->reset_branch($host_path, $base_branch, $upstream_repo, $fork_repo, $new_branch));
      //      $this->taskExec($this->check_success($result, "Set up git"));
    }

    // See if there's anything new to install from Composer.
    $this->say("I'm going to see if there's anything to install.");
    $result = $this->taskComposerInstall()
      ->dir($host_path)
      ->dev()
      ->run();
    $this->taskExec($this->check_success($result, "Composer install"));

    // Turn on the VM and reprovision it if necessary.
    if (!$opts['no-provision']) {
      $this->catlet("Let's turn this thing on.");
      $result = $this->taskExecStack()
        ->stopOnFail()
        ->dir($host_path)
        ->exec($vm_start)
        ->run();
      $this->taskExec($this->check_success($result, $vm_start));
    }
    elseif ($vm_start = 'vagrant up --provision') {
      $this->say("I will turn on the existing VM but won't reprovision.");
      $result = $this->taskExecStack()
        ->stopOnFail()
        ->dir($host_path)
        ->exec('vagrant up')
        ->run();
      $this->taskExec($this->check_success($result, 'vagrant up'));
    }

    // Run tasks inside the VM
    $result = $this->refresh_ticket($ssh_commands);

    if (!$result->wasSuccessful()) {
      $this->io()->error("Sorry, I was not able to finish setup.");
      return 1;
    }

    // Cleanup commands to be run after everything else.
    $this->say("Performing final cleanup steps . . .");
    foreach ($teardown_commands as $each) {
      if (!empty($each)) {
        exec($each);
      }
    }

    // Outro
    $this->say("Congratulations, we're done!  Here's what we did:");
    $this->io()->listing($tasks);
    $this->stopwatch($start_time);
    $this->catlet("Go forth and be awesome.");
    return;
  }

  /**
   * Calculate & report elapsed time for this task.
   *
   * @param $start_time
   *   The time you began.
   */
  function stopwatch($start_time) {
    $stop_time = time();
    $elapsed_time = $stop_time - $start_time;
    $elapsed_minutes = floor($elapsed_time / 60);
    $elapsed_seconds = $elapsed_time - $elapsed_minutes * 60;
    $this->say("This took $elapsed_minutes minutes and $elapsed_seconds seconds.");

    return;
  }

  /**
   * Reset the local git branch to a fresh copy of master, upload to your fork
   * repository, and set upstream.
   *
   * @see: https://stackoverflow
   * .com/questions/5288172/git-replace-local-version-with-remote-version
   *
   * @param $host_path
   * @param $base_branch
   * @param $upstream_repo
   * @param $fork_repo
   * @param $new_branch
   *
   * @return void
   */
  function reset_branch($host_path, $base_branch, $upstream_repo, $fork_repo,
                        $new_branch) {
    $this->taskGitStack()
      ->stopOnFail()
      ->dir($host_path)
      ->checkout("-B $base_branch $upstream_repo/$base_branch")
      ->pull($upstream_repo, $base_branch)
      ->push($fork_repo, $base_branch)
      ->exec("git branch -D $new_branch")
      ->checkout("-B $new_branch $upstream_repo/$base_branch")
      ->exec("git push $fork_repo $new_branch --set-upstream")
      ->run();

    return;
  }

  /**
   * Install all your favorite things on a new environment.
   *
   * Uses Homebrew to automatically install a bunch of software on your Mac.
   * If you don't have Homebrew, it installs it for you first.
   *
   * Note: Homebrew comes with two repositories: regular "brews" and also
   * "casks."  This script pulls software from both, but the command to
   * install is different (`brew install` vs. `brew cask install`).
   *
   * @TODO automatically detect environment
   *
   * @option $mac Install Mac-related packages
   * @option $y Install Homebrew
   *
   * @throws \Robo\Exception\TaskException
   */
  function new_environment($opts = ['y' => FALSE, 'mac' => TRUE]) {
    // Load config & set environment variables
    $start_time = time();
    $brews = Robo::config()->get("command.new_environment.brews");
    $casks = Robo::config()->get("command.new_environment.casks");
    //    $pwd = exec('cd ~; pwd');
    $pwd = exec('pwd');
    $home = exec('echo $HOME');

    if ($opts['mac']) {
      if ($opts['y']) {
        // Install Homebrew.
        $result = $this->taskExecStack()
          ->exec('/usr/bin/ruby -e "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/master/install)"')
          ->run();
        $this->check_success($result, 'Installing Homebrew');
      }
      else {
        $this->io()->note("If you add --y, I'll install Homebrew.");
      }

      // install apps using Homebrew
      $repos = [
        'brew install' => $brews,
        'brew cask install' => $casks,
      ];

      // Loop through both arrays.  Create a collection.
      $collection = $this->collectionBuilder();
      foreach ($repos as $command => $desires) {
        foreach ($desires as $desire) {
          $collection->taskExecStack()
            ->exec($command . " " . $desire);
        }
      }
      $collection->run();
    }
    else {
      $this->io()
        ->note("If you specify an operating system, I can install a lot more stuff.");
    }

    // Run `composer install`.
    $this->taskComposerInstall()->run();

    // Install .bash_profile and other items consistent with all Linux & Unix environments
    $files = [
      '.bash_profile' => 'bash profile: better command line prompt & command aliases',
      '.bash_logout' => 'cute farewell greeting when you log out',
      '.vimrc' => 'better VI settings',
    ];
    foreach ($files as $file => $description) {
      $collection->copy("$pwd/$file", "$home/$file");
    }
    $collection->run();

    // Outro
    $this->stopwatch($start_time);
    $this->catlet("All Done!");
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
    $this->taskExec('sh vendor/btford/allthethings/allthethings.sh')
      ->silent(TRUE)
      ->printOutput(TRUE)
      ->run();

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

    $this->catlet("All done!");
    $this->io()->success("Pat yourself on the back for a job well done.");
  }

  /**
   * Reusable function to report whether the previous step succeeded.
   *
   * @param object $result
   *   Pass the result object to this function.
   * @param string $task
   *   Plain text description of the currently running task.
   *
   * @return int
   *   Should correctly report whether task was successful or not.
   */
  function check_success($result = NULL, $task = "Current task") {
    if (!$result) {
      $message = $this->io()->error("Sorry, something went wrong with $task");
      exit($message);
    }
    elseif ($result->wasSuccessful()) {
      $this->io()->success("$task was successful!");
      return 0;
    }
    else {
      $message = $this->io()->error("Sorry, something went wrong with $task");
      exit($message);
    }
  }

  /**
   * Refresh local environment for existing ticket.
   *
   * This is a lighter lift than new_ticket().  Here, we don't rebuild the
   * environment; we simply reload stored config for Drupal _inside_ the VM.
   */
  function refresh_ticket($ssh_commands = NULL) {
    // Introduction.
    $this->catlet("Refresh Drupal Config!");

    // Initial setup: retrieve stored preferences.
    $allopts = Robo::config()->get("command.new_ticket.options");
    foreach ($allopts as $key => $value) {
      $$key = $value;
    }

    // If you call this function from inside another function, you can pass
    // the ssh commands as an argument.  Otherwise, if you call this function
    // by itself (eg. `ph refresh_ticket`), it will grab the VM commands from
    // the yaml file and run them.
    if (!$ssh_commands) {
      $ssh_commands = $this->taskExecStack();
      foreach ($vm_commands as $key => $value) {
        $ssh_commands->exec($value);
      }
    }

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

    $this->catlet("All done!");

    return $result;
  }

  /**
   * Check Application
   *
   * Accepts a string
   * @param string $check
   *
   * @return string
   */
  function check_app(string $check) {
    $found = exec("which $check");
    $result = $found ? $found : 'echo';

    return $result;
  }

  /**
   * Say something using figlet and lolcat.
   *
   * Figlet outputs a string using bubble letters, and lolcat outputs the
   * string with rainbow letters.  Example:
   *   _   _      _ _        __        __         _     _
   *  | | | | ___| | | ___   \ \      / /__  _ __| | __| |
   *  | |_| |/ _ \ | |/ _ \   \ \ /\ / / _ \| '__| |/ _` |
   *  |  _  |  __/ | | (_) |   \ V  V / (_) | |  | | (_| |
   *  |_| |_|\___|_|_|\___/     \_/\_/ \___/|_|  |_|\__,_|
   *
   * @param string $say
   *   String to be rendered
   *
   * @return void
   */
  function catlet(string $say = 'Hello World') {
    $checks = ['lolcat', 'figlet', 'cowsay', 'fortune'];
    $lolcat = exec('which lolcat');
    foreach ($checks as $check) {
      $found = exec("which $check");
      $$check = $found ? $found : 'echo';
    }
    $this->taskExec("$figlet $say | $lolcat")
      ->silent(TRUE)
      ->printOutput(TRUE)
      ->run();

    return;
  }

  /**
   * Reusable introduction.
   *
   * This function takes 3 arguments: your header, your intro, and a list of
   * things.  The headline gets displayed in figlet default puffy letter
   * style, your introduction gets displayed as a simple say(), and the list
   * rendered using io()->listing().  Finally, it asks the user if they want
   * to continue.  Continuing is a simple matter of pressing "enter"; if they
   * do, then the function will return TRUE (in case you need it).  If they
   * type ctl-C, then they will escape the entire program.
   *
   * @param string $banner
   *   Say it in lights.
   * @param string $intro
   *   Say it in normal words.
   * @param null $list
   *   What are you going to do?
   *
   * @return bool
   */
  function intro(string $banner = 'Hi!', string $intro = '', $list = NULL) {
    // Can't pass NULL to io->listing().
    $list = $list ? $list : ["(Sorry, not actually sure what I *can* do!)"];

    // Display in glorious fashion
    $this->catlet($banner);
    $this->say($intro . "  Here's what I can do:");
    $this->io()->listing($list);

    // Simple confirmation: anything to continue; ctl-C to escape.
    $this->say("Do you want me to do this?");
    $this->ask("Press [ENTER] to continue, ctrl-C to cancel.");

    return;
  }

  /**
   * Tail wags the Watchdog.
   *
   * `drush ws --tail` has been deprecated in drush 9.  This command
   * replicates that functionality inside your local vm.  No arguments
   * needed; this function uses the same config as new_ticket, stored in robo
   * .yml.
   */
  function wag() {
    // Get config from robo.yml.
    $allopts = Robo::config()->get("command.new_ticket.options");
    foreach ($allopts as $key => $value) {
      $$key = $value;
    }

    // Run tasks inside the VM
    $this->say("I'm going to tail the Drupal watchdog log.  Type ctrl-c to stop.");
    sleep(2);
    $this->taskSshExec($vm_domain, $vm_user)
      ->stopOnFail()
      ->silent(TRUE)
      ->printOutput(TRUE)
      ->forcePseudoTty()
      ->dir($host_path)
      ->port($vm_port)
      ->identityFile($vm_key)
      ->remoteDir($guest_path)
      ->exec('watch -n 1 drush ws')
      ->run();
  }
}
