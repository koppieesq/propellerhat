<?php

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
   * Resets your local dev environment to start a new task.
   *
   * Credits:
   *
   * @see https://git.businesswire.com/projects/HQ/repos/hq-tools/browse/dev/reset-dev
   *      With gratitude to Ben Thornton
   * @see https://github.com/g1a/starter
   *      And hat tip to G1A
   */
  function new_ticket() {
    $this->say("Hi!
I'm going to help you refresh your local dev environment to start a new ticket.");
    // @TODO: actual environment detection
    $new_branch = "T-1000-sarah-connor";
    $pwd = "/Library/WebServer/Documents/propellerhat";
    
    // @TODO: put config in yaml files!
    $upstream_repo = "origin";
    $fork_repo = "fork";
    $base_branch = "master";
    $guest_path = "/var/www";
    $runner = "composer install";
    $vm_start = "vagrant up --provision";
    $vm_ssh = "vagrant ssh -c";
    $vm_domain = "localhost";
    $vm_user = "vagrant";
    
    // List of commands to be run inside the VM:
    $commands = [
      "blt setup -n",
      "drush cim -y",
      "drush updb -y",
      "drush cr",
      "drush uli",
    ];
    
    // Description of tasks to be performed, repeats at both the beginning and end of the script.
    $tasks = [
      "Pull a fresh copy of $upstream_repo/$base_branch",
      "Push to $fork_repo/$base_branch",
      "Push to $fork_repo/$new_branch and set upstream",
      "Task runner: $runner",
      "Turn on the virtual machine: $vm_start",
      "Run commands inside the VM",
    ];
    
    $requirements = [
      "You're in the base directory for your project",
      "You have forked the 'upstream' repository and created your own",
      "You've already created a feature branch for the new ticket",
      "You've updated the enclosed 'config' file with the correct repositories and branches",
    ];
    
    // @TODO: make more steps optional
    $this->io()->section("Here's what I can do:");
    $this->io()->listing($tasks);
    $this->io()->section("In order for this to work, please make sure:");
    $this->io()->listing($requirements);
    $this->ask("Press Enter to continue, or ctrl-c to cancel.");
    
    // actual steps go here
    $this->taskGitStack()
      ->stopOnFail()
      ->checkout($base_branch)
      ->pull($upstream_repo, $base_branch)
      ->push($fork_repo, $base_branch)
      ->checkout($new_branch)
      ->exec("git push $fork_repo $new_branch --set-upstream")
      ->run();
    
    // See if there's anything new to install from Composer.
    $this->say("I'm going to see if there's anything to install.");
    $this->taskComposerInstall()->run();
    
    // Turn on the VM and reprovision it if necessary
    $this->say("Let's turn this thing on.");
    $this->taskExec($vm_start)->run();
    
    // Run tasks inside the VM
    $this->say("I'm going to run some commands inside the VM now.");
    $this->taskSshExec($vm_domain, $vm_user)
      ->remoteDir($guest_path)
      ->exec("blt setup -n")
      ->exec("drush cim -y")
      ->exec("drush updb -y")
      ->exec("drush cr")
      ->exec("drush uli");
    
    // Outro
    $this->say("Congratulations, we're done!  Here's what we did:");
    $this->io()->listing($tasks);
    $this->io()->note("Now go forth and be awesome.");
  }
}