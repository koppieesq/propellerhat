<?php

use Robo\Robo;
use MC\MC;
require __DIR__ . '/vendor/autoload.php';

/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see     http://robo.li/
 *
 * @warning This tool is still in beta!  See @todos.
 */
class RoboFile extends \Robo\Tasks {
  // Another load statement - this is the secret sauce.
  use MC;

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
   * @option $y Install Homebrew
   *
   * @throws \Robo\Exception\TaskException
   */
  function new_environment($opts = ['y' => FALSE]) {
    // Load config & set environment variables
    $start_time = time();
    $brews = Robo::config()->get("command.new_environment.brews");
    $casks = Robo::config()->get("command.new_environment.casks");
    $debian = Robo::config()->get("command.new_environment.debian");
    $pwd = exec('pwd');
    $home = exec('echo $HOME');
    $environment = exec('uname');

    if ($environment == 'Darwin') {
      if ($opts['y']) {
        // Install Homebrew.
        $result = $this->taskExecStack()
          ->exec('/usr/bin/ruby -e "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/master/install)"')
          ->run();
        $this->mc()->check_success($result, 'Installing Homebrew');
      }
      else {
        $this->io()->note("If you add --y, I'll install Homebrew.");
      }

      // install apps using Homebrew
      $repos = [
        'brew install' => $brews,
        'brew cask install' => $casks,
      ];

      // Loop through arrays.
      foreach ($repos as $command => $desires) {
        $temp_string = '';
        foreach ($desires as $desire) {
          $temp_string .= ' ' . $desire;
        }
        $this->_exec($command . $temp_string);
      }

      // Diff-so-fancy installed separately because there's an extra command.
      // @see https://github.com/so-fancy/diff-so-fancy
      $string = "Turning your ";
      $string .= $this->mc()->tput("git diff", ["color" => "cyan"]);
      $string .= " into ";
      $string .= $this->mc()->tput("diff-so-fancy", ["color" => "magenta"]);
      $this->say($string);
      $this->taskExecStack()
        ->exec("brew install diff-so-fancy")
        ->exec('git config --global core.pager "diff-so-fancy | less --tabs=4 -RFX"')
        ->run();
    }
    elseif ($environment == 'Linux') {
      // Install debian packages here
      $temp_string = '';
      // Loop through arrays.
      foreach ($debian as $desire) {
        $temp_string .= ' ' . $desire;
      }
      $this->_exec('sudo apt-get install ' . $temp_string);
    }
    else {
      $this->io()->warning("Sorry, I don't recognize your operating system.");
    }

    // Run `composer install`.
    $this->say("Composer install");
    $this->taskComposerInstall()->run();

    // Install .bash_profile and other items consistent with all Linux & Unix environments
    $this->say("Copying environment files:");
    $files = [
      '.bash_profile' => 'bash profile: better command line prompt & command aliases',
      '.bash_logout' => 'cute farewell greeting when you log out',
      '.vimrc' => 'better VI settings',
    ];
    foreach ($files as $file => $description) {
      $this->say("Installing " . $description);
      $this->taskFilesystemStack()->copy("$pwd/$file", "$home/$file")->run();
    }

    // Outro
    $this->mc()->stopwatch($start_time);
    $this->mc()->catlet("All Done!");
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

    $this->mc()->catlet("All done!");
    $this->io()->success("Pat yourself on the back for a job well done.");
  }

  /**
   * Robo implementation of Git Rebase
   *
   * This function performs a git rebase for you.  It starts in your base
   * directory in your feature branch, pulls down a fresh copy of master, and
   * then rebases your feature branch over master.  That's it!
   *
   * @return int
   */
  function rebase() {
    $allopts = Robo::config()->get("command.new_ticket.options");
    foreach ($allopts as $key => $value) {
      $$key = $value;
    }
    $host_path = $_SERVER['HOME'] . '/' . $host_path;

    // Load config & set environment variables
    $start_time = time();
    $new_branch = exec("cd $host_path; git symbolic-ref --short HEAD");
    $banner = 'All Your Rebase Are Belong To Us';
    $intro = 'You have no chance to survive make your time.';
    $color = ['color' => 'cyan'];
    $tasks = [
      "Rebase " . $this->mc()->tput($new_branch, $color) . " over a fresh copy of " . $this->mc()->tput('master', $color),
    ];
    $this->mc()->intro($banner, $intro, $tasks);

    $this->taskGitStack()
      ->stopOnFail()
      ->dir($host_path)
      ->checkout('master')
      ->pull()
      ->checkout($new_branch)
      ->exec("git rebase master")
      ->run();

    $this->mc()->stopwatch($start_time);
    $this->mc()->catlet("For great justice");

    return 0;
  }

  /**
   * Refresh local environment for existing ticket.
   *
   * This is a lighter lift than new_ticket().  Here, we don't rebuild the
   * environment; we simply reload stored config for Drupal _inside_ the VM.
   */
  function refresh_ticket($ssh_commands = NULL, string $banner = "Refresh Drupal Config!") {
    // Introduction.
    $this->mc()->catlet($banner);

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
      ->stopOnFail(FALSE)
      ->port($vm_port)
      ->identityFile($vm_key)
      ->remoteDir($guest_path)
      ->dir($host_path)
      ->exec($ssh_commands)
      ->run();
    $this->taskExec($this->mc()->check_success($result, "Commands inside the VM"));

    $this->mc()->catlet("All done!");

    return $result;
  }

  /**
   * Tail wags the Watchdog.
   *
   * `drush ws --tail` has been deprecated in drush 9.  This command
   * replicates that functionality inside your local vm.  No arguments
   * needed; this function uses the same config as new_ticket, stored in robo
   * .yml.
   *
   * @param string|null $site
   *   Optional: specify site to target.
   *
   * @throws \Robo\Exception\TaskException
   */
  function wag(string $site = NULL) {
    // Get config from robo.yml.
    $allopts = Robo::config()->get("command.new_ticket.options");
    foreach ($allopts as $key => $value) {
      $$key = $value;
    }
    $host_path = $_SERVER['HOME'] . '/' . $host_path;
    $vm_key = $_SERVER['HOME'] . '/' . $vm_key;
    $wait = 2;

    // Run tasks inside the VM
    $this->say("I'm going to tail the Drupal watchdog log.  Type ctrl-c to stop.");
    sleep($wait);

    if ($site) {
      // If you're targeting a specific site, then run the command against it.
      while (1 + 1 == 2) {
        $this->taskExecStack()
          ->silent(TRUE)
          ->printOutput(TRUE)
          ->dir($host_path)
          ->exec("drush $site ws")
          ->run();
      }
    }
    else {
      // Otherwise, just grab default ssh info from robo.yml and use that.
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

  /**
   * Log in as Drupal superuser on local VM.
   *
   * @param string $name
   *   Optional: user name
   */
  function uli(string $name = "") {
    // Get config from robo.yml.
    $allopts = Robo::config()->get("command.new_ticket.options");
    foreach ($allopts as $key => $value) {
      $$key = $value;
    }

    $add_name = $name ? "--name=$name" : "";

    $this->taskSshExec($vm_domain, $vm_user)
      ->stopOnFail()
      ->printOutput(TRUE)
      ->forcePseudoTty()
      ->dir($_SERVER['HOME'] . '/' . $host_path)
      ->port($vm_port)
      ->identityFile($_SERVER['HOME'] . '/' . $vm_key)
      ->remoteDir($guest_path)
      ->exec("drush uli --uri local.bwd.com $add_name")
      ->run();
  }

}