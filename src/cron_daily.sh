#!/bin/bash
#
# Cron master script for SproutCMS, version 4.
#
# Depending on your setup, you may need to modify this
# such as the $PHP variables, and the defines used.
#


# Look in a few different places for a PHP CLI binary.
# I know these should be nested, but that would be very hard to read.
PHP="/usr/bin/php-cli"
if [ ! -x $PHP ]; then
	PHP="/usr/local/bin/php"
fi
if [ ! -x $PHP ]; then
	PHP="/usr/bin/php"
fi
if [ ! -x $PHP ]; then
	echo "Unable to detect a working PHP version.";
	exit;
fi

# We can't use a CGI binary. It just doesn't work at all.
if [ "`$PHP --version | grep -i 'cgi'`" != "" ]; then
	echo "Found PHP binary is CGI. This is unusable.";
	exit;
fi


# A bit of logging
echo "This script:   $0"
echo -n "Server:        "
uname -a
echo "PHP found at:  $PHP"
echo -n "PHP version:   "
$PHP --version | head -n1
echo


# Drop into the directory
cd `dirname $0`

# Let the scripts know it's a cron
export CRON=1


#
# Cron jobs to run are registered using the Register::cronJob method in sprout_load.php
#
# An example registration would be:
#    Register::cronJob('daily', 'Sprout\\Controllers\\Admin\\PageAdminController', 'cronPageActivate');
#
# Multiple 'schedules' can be defined; this is the last argument of the run method
# To create another schedule (e.g. 'weekly'), duplicate this file and modify the line
# below to 'cron_job/run/weekly' then register your jobs using Register::cronJob('weekly', ...)
# In this case you'd probably also want to remove the temp files cleanup command as well.
#

$PHP -d "safe_mode=0" index.php "cron_job/run/daily"


# Clean up any temp files older than 1 day
find sprout/temp -mtime +1 -type f ! -name 'index.htm' -exec rm -f {} \;
