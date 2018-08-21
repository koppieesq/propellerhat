#!/usr/bin/env bash

# Prepare for a fresh Drupal 8 branch.
# Assumes that you:
# - are currently in the target directory
# - already have a feature branch checked out

set -x

project="bwd"
upstream_repo="origin"
fork_repo="fork"
master_branch="master"
which_branch=$(git branch)
new_branch=${which_branch##** }
repo_path=$(pwd)
guest_path="/var/www/$project"

# Even though we've already created a feature branch, we want to refresh master, and also 
git checkout "$base_branch" || {
    >&2 echo "fatal: cannot checkout $base_branch"
    exit 3
}

git pull $upstream_repo $master_branch
git push $fork_repo $master_branch
git checkout $new_branch

See if there's anything new to install from Composer.
composer install || {
    >&2 echo "fatal: cannot update local files via composer"
    exit 4
}

vm_status="$(vagrant global-status | grep bwd | tr -s ' ' | cut -d ' ' -f 4)"
if [ "$vm_status" != "running" ]; then
    vagrant up || {
        >&2 echo "fatal: cannot boot the virtual machine"
        exit 5
    }
fi

vagrant provision

# Rebuild the site and run the automated tests.
vagrant ssh -c "cd '$guest_path'; blt setup -n"

# Import stored config.
vagrant ssh -c "cd '$guest_path'; drush cim -y"

# Generate a valid URL for a one-time login.
vagrant ssh -c "cd '$guest_path'; drush uli --uri local.bwd.com"
