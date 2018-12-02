# propellerhat
For your inner child - the nerdy one who has a little cap with a propeller on the top.

Some of these are bash scripts, but most use [Robo](https://robo.il).

## Contents:
- **.bash_profile:** Custom settings for your command line interface
- **.bash_logout:** Cute script that runs when you end a terminal session.
- **.vimrc:** Custom settings for the VI text editor
- **new_ticket:** Refresh your local dev environment when you start a new task
  - *usage:* `robo new_ticket`
  - *shortcut:* `robo nt`
- **updateme:** Update contrib code on all your Drupal sites at once.
  - *usage:* `robo updateme`
- **new_environment:** Install all your favorite tools.  See below for instructions.
  
- **Obsolete tools:** These are some nifty tools that I wrote, but they've been supplanted by much better, open source tools written by other people.
    - **db down:** Sync database from a [Pantheon](https://pantheon.io) site.
      - *You should probably use [Terminus](https://github.com/pantheon-systems/terminus) instead.*
    - **upload key:** Uploads your SSH key.
      - *You should probably use `ssh-copy-id` instead.*
      
## New Environment
### *Installation Instructions*
The new_environment() function installs all your favorite tools when you have a new local environment (for example, when you get a new workstation).  However, it presents the classic 'chicken & egg' problem: how are you supposed to install anything without your dev tools?

Fortunately it's easy:
1. Install xcode command line tools.  This gets you git.  ;-)
```bash
xcode-select --install
```
1. Use git to clone propellerhat to your local environment
1. Install [Robo](https://robo.li/)
1. Run the new_environment installer and specify environment.  Currently only supports Mac:
```bash
robo new_environment mac
```

## Configuration
You can control how certain scripts behave.  Copy `default.robo.yml` and save it as `robo.yml` in the same directory.  You can also store `robo.yml` in your home directory (`~`).

### Configuring new_ticket
The following variables can be easily changed in the config file:
- **upstream_repo:** Name of the upstream repository in git
- **fork_repo:** Name of your forked version of the repository
- **base_branch:** The master branch.  Usually `master` (but not always)
- **guest_path:** The path to your codebase inside the VM
- **runner:** Use this to run task runners like composer or npm.  Runs outside the VM.
- **vm_start:** Command to start & reprovision the VM
- **vm_ssh:** How to send commands from the host to the VM
- **commands:** List of commands to be run inside the VM.  Default examples:
  - `blt setup -n`: Uses the Acquia blt utility to set up local env
  - `drush cim -y`: Import Drupal config
  - `drush updb -y`: Run database updates
  - `drush cr`: Reset cache
  - `drush uli`: Log in and provide a link that's viewable from the host OS
  
# Roadmap
The following new features are contemplated for the future:
- More flexible workflow:
  - Fork the repo
  - Create the feature branch
- Wizard to create new config file
  - Store config file in project or global (user directory)
  - Create multidimensional array for config values, including key, default, & description
  - Remove robo.yml, keep default.robo.yml
- Support installation via Composer
- Integrate with PhpStorm
- Use collectionBuilder
- Add additional items to new_environment():
  - start with `composer install`
  - Additional config files: .bash_profile, .bash_logout, .vimrc, alias for Robo commands
  - Additional utilities: bat, glances, diff-so-fancy (and replace git diff), middleclick, signal, multimonitor wallpaper, vlc, monit, synergy, filezilla
  - Add optional toys: steam, bitlord, wesnoth, minecraft
  - Add executable: edex-ui
  - Remove Homebrew version of git
    @see https://github.com/Homebrew/homebrew-core/issues/31980#issuecomment-425894125
  
  
# Credits

Brought to you by [Jordan Koplowicz](http://koplowiczandsons.com).  This software is free to use, modify, and distribute under the GPL 3 license.

With gratitude to [Greg Andersen](https://github.com/g1a/starter) and [Ben Thronton](https://git.businesswire.com/projects/HQ/repos/hq-tools/browse/dev/reset-dev).