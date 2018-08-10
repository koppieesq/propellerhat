#!/usr/bin/env bash

# First command line argument is the target site.  Second argument is the target environment.
SITE=$1
ENV=$2

# Check for progress bar utilities (optional)
CHECK1=$(which pv)
CHECK2=$(which bar)

if [ -z "$SITE" ]; then
    echo "You need to specify the site you want to download from."
    exit 1
fi

# If no environment, then default to dev
if [ -z "$2" ]; then
	ENV='dev'
fi
NOW=$(date +"%Y%m%d")
OUTPUT=$HOME/Downloads/$SITE.$ENV$NOW.sql.gz

# Check Drupal 7 or Drupal 8
VERSION=$(drush status | grep 'Drupal version')
if [[ $VERSION == *":  8."* ]]; then
  DCA='drush cr'
else
  DCA='drush cc all'
fi

echo "Hello!  Welcome to DB Down."
echo "I will download the database from the \033[1;36m$ENV\033[0m environment of \033[1;36m$SITE\033[0m"
echo "Then I will load it into your local environment."
echo "\033[1;31mYou should run this command from your local Drupal environment. \033[0m"

echo "Authenticating . . ." $SITE
terminus auth:login
echo "Creating a new database dump . . ."
terminus backup:create --element=database $SITE.$ENV
echo "Downloading to $HOME/Downloads"
terminus backup:get --element=database --to=$OUTPUT $SITE.$ENV
echo "Now I need to unzip \033[1;36m$OUTPUT\033[0m and load into your local environment"

# Use a progress bar if available.  If not, then do it silently.
if [ ! -z "$CHECK1" ]
then
	$CHECK1 $OUTPUT | zcat | drush sqlc
elif [ ! -z "$CHECK2" ]
then
	$CHECK2 $OUTPUT | zcat | drush sqlc
else
	echo "Hint: You can see the progress if you install \033[1;34mpv\033[0m or \033[1;34mbar\033[0m"
	gunzip < $OUTPUT | drush sqlc
fi

echo "All set!  Now a little housekeeping."
drush updb -y
$DCA
