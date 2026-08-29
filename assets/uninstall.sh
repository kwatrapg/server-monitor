#!/bin/bash
#
#////////////////////////////////////////////////////////////
#===========================================================
# Sentruo - Uninstaller v1.0
#===========================================================
# Set environment
PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin

# Clear the screen
clear

#SERVERKEY=$1
LOG=/var/log/sentruo-agent.log

echo "---------------------------------"
echo "Sentruo Linux Agent Uninstaller"
echo "---------------------------------"
echo " "

# Are we running as root
if [ $(id -u) != "0" ]; then
	echo "Sentruo Agent uninstaller needs to be run with root priviliges"
	echo "Try again with root privilileges"
	exit 1;
fi


# Remove previous installation
if [ -f /opt/sentruo/agent.sh ]; then
	# Remove folder
	rm -rf /opt/sentruo
	# Remove crontab
	crontab -r -u sentruo-agent >> $LOG 2>&1
	# Remove user
	userdel sentruo-agent >> $LOG 2>&1
fi

echo " "
echo "-------------------------------------"
echo "Uninstallation Completed "
echo "-------------------------------------"
