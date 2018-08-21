# propellerhat
For your inner child - the nerdy one who has a little cap with a propeller on the top.

**Contents:**
- **.bash_profile:** Custom settings for your command line interface
- **.vimrc:** Custom settings for the VI text editor
- **db down:** Downloads a database from a [Pantheon](https://pantheon.io) site and loads it into your local environment
- **upload key:** Upload your SSH key to a server in a single command!
- **new-ticket:** Refresh your local dev environment when you start a new task

## Configuration
You can edit the included `config` file to control some variables in certain tools.

### Configuring new-ticket
The following variables can be easily changed in the config file:
- upstream_repo
- fork_repo
- base_branch
- guest_path
- runner
- vm_start
- vm_ssh

See the config file for explanations of each variable.

# List of commands to be run inside the VM:
commands=(
    "blt setup -n"                  # Uses the Acquia blt utility to set up local env
    "drush cim -y"                  # Import Drupal config
    "drush uli --uri local.bwd.com" # Log in and provide a link that's viewable from the host OS
    )


# Credits

Brought to you by [Jordan Koplowicz](http://koplowiczandsons.com).  This software is free to use, modify, and distribute under the GPL 3 license.
