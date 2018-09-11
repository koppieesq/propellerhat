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
   * @var string $fork_repo     Name of your forked repository
   * @var string $base_branch   Name of the master branch.  Usually "master".
   * @var string $new_branch    Name of your feature branch.
   * @var string $runner        Task runner / package manager, eg. Composer.
   * @var string $vm_start      Command to start the virtual machine
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
   */
  function new_ticket() {
    $this->say("Hi!  I'm going to help you refresh your local dev environment to start a new ticket.");
    
    // Load config & set environment variables
    $new_branch = exec("git symbolic-ref --short HEAD");
    $allopts = Robo::config()->get("command.new_ticket.options");
    foreach ($allopts as $key => $value) {
      $$key = $value;
    }
    
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
    $this->io()->text("Here's what I can do:");
    $this->io()->listing($tasks);
    $this->io()->text("In order for this to work, please make sure:");
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
}