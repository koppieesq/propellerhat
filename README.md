# propellerhat
[![github](https://img.shields.io/badge/github-0a0.svg?logo=github)](https://github.com/koppieesq/propellerhat)
[![packagist](https://img.shields.io/badge/packagist-orange.svg?logo=php&logoColor=white)](https://packagist.org/packages/koppieesq/propellerhat)
[![License](https://img.shields.io/badge/license-GPL3-teal.svg?logo=gnu)](LICENSE)

For your inner child - the nerdy one who has a little cap with a propeller on the top.

Some of these are bash scripts, but most use [Robo](https://robo.li).

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
### Installation Instructions
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

## Communication Tools
- **Google Chrome:** Web browser from Google.
- **Firefox:** Web browser from Mozilla Foundation.
- **Slack:** Instant messenger program popular with tech companies.
- **Skype:** Instant messenger program from Microsoft.

### Command Line Tools
- **Hub:** Command line tool for GitHub.  Lets you submit pull requests.
- **ssh-copy-id:** Copy your public ssh key to a new server.
- **bar:** Add a progress bar to long tasks, like database import.
- **bat:** `cat` command on steroids.
- **htop:** `top` command on steroids.
- **Glances:** `top` command on steroids.
- **exa:** `ls` command on steroids.
- **z:** Improves behavior of ls with autocomplete.

### Mac OS X Tools
- **1 Clipboard:** Clipboard memory tool
- **Bearded Spice:** Use your Mac function keys to control any music player.
- **Spectacle:** Control window layout with hotkeys.
- **Synergy:** Control multiple computers with a single keyboard & mouse.  Cross-platform.

### Development Tools
- **Ansible:** Used to manage virtual servers.
- **Composer:** Package manager for PHP.
- **Docker:** Platform for virtual servers.
- **Chromedriver:** Control Google Chrome with Selenium Webdriver.
- **Lando:** Tool for managing local development environments.
- **Livereload:** Daemon watches a local folder for changes, then reloads your web browser.
- **PHPStorm:** Integrated Development Environment.  Non-free product.
- **Virtualbox:** Platform for virtual servers.
- **Source Code Pro:** Beautiful fixed-width font, perfect for your IDE.

### Fun Tools
- **Cowsay:** Wraps your command line output in a word bubble, spoken by a cow.
- **Figlet:** Renders command line output as big, puffy letters.
- **Fortune:** Tells you your fortune.
- **Lolcat:** Output command line text in rainbow colors.
- **Spotify:** Play any song legally, for free.
- **VLC:** 3rd party video player supports any video format.
- **Edex-UI:** Make your computer look like a movie hacker.  Includes fully functional CLI.

### Support Libraries
The following are libraries, installed as prerequisites for other tools on 
the list: glib, node, nvm, php@7.1, java

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
- Add additional items to new_environment():
  - Alias for Robo commands
  - Add optional toys: steam, bitlord, wesnoth, minecraft
  
# Credits

Brought to you by [Jordan Koplowicz](http://koplowiczandsons.com).  This software is free to use, modify, and distribute under the GPL 3 license.

With gratitude to [Greg Andersen](https://github.com/g1a/starter) and [Ben Thronton](https://git.businesswire.com/projects/HQ/repos/hq-tools/browse/dev/reset-dev).