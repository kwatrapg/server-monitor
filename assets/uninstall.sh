#!/bin/bash
#
#////////////////////////////////////////////////////////////
#===========================================================
# nMom - Uninstaller v1.0
#===========================================================
# Set environment
PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin

# Clear the screen
clear

#SERVERKEY=$1
LOG=/tmp/pniner.log

echo "---------------------------------"
echo "Server-Monitor Linux Agent Uninstaller"
echo "---------------------------------"
echo " "

# Are we running as root
if [ $(id -u) != "0" ]; then
	echo "Server-Monitor Agent uninstaller needs to be run with root priviliges"
	echo "Try again with root privilileges"
	exit 1;
fi


# Remove previous installation
if [ -f /opt/server-monitor/agent.sh ]; then
	# Remove folder
	rm -rf /opt/server-monitor
	# Remove crontab
	crontab -r -u server-monitoragent >> $LOG 2>&1
	# Remove user
	userdel server-monitoragent >> $LOG 2>&1
fi

echo " "
echo "-------------------------------------"
echo "Uninstallation Completed "
echo "-------------------------------------"
