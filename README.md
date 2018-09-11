# propellerhat
For your inner child - the nerdy one who has a little cap with a propeller on the top.

Some of these are bash scripts, but most use [Robo](https://robo.il).

**Contents:**
- **.bash_profile:** Custom settings for your command line interface
- **.bash_logout:** Cute script that runs when you end a terminal session.
- **.vimrc:** Custom settings for the VI text editor
- **db down:** Downloads a database from a [Pantheon](https://pantheon.io) site and loads it into your local environment
  - *usage:* `sh dbdown $SITE`
- **upload key:** Upload your SSH key to a server in a single command!
  - *usage:* `sh upload-key`
- **new_ticket:** Refresh your local dev environment when you start a new task
  - *usage:* `robo new_ticket`
  - *shortcut:* `robo nt`
- **updateme:** Update contrib code on all your Drupal sites at once.
  - *usage:* `robo updateme`

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

# Credits

Brought to you by [Jordan Koplowicz](http://koplowiczandsons.com).  This software is free to use, modify, and distribute under the GPL 3 license.

With gratitude to [Greg Andersen](https://github.com/g1a/starter) and [Ben Thronton](https://git.businesswire.com/projects/HQ/repos/hq-tools/browse/dev/reset-dev).