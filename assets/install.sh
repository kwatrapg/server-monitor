#!/bin/bash
#
#////////////////////////////////////////////////////////////
#===========================================================
# Sentruo - Installer v1.1
#===========================================================
# Set environment
PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin

# Clear the screen
clear

#SERVERKEY=$1
#GATEWAY=$2
LOG=/var/log/sentruo-agent.log

echo "--------------------------------"
echo " Welcome to Sentruo Agent Installer"
echo "--------------------------------"
echo " "

# Are we running as root
if [ $(id -u) != "0" ]; then
	echo "Sentruo Agent installer needs to be run with root priviliges"
	echo "Try again with root privilileges"
	exit 1;
fi

# Is the server key parameter given ?
if [ $# -lt 2 ]; then
	echo "The server key or gateway is missing"
	echo "Exiting installer"
	exit 1;
fi

### install Dependencies here
echo "Installing Dependencies"

# RHEL / CentOS / etc
if [ -n "$(command -v yum)" ]; then
	yum -y install cronie gzip curl openssl >> $LOG 2>&1
	service crond start >> $LOG 2>&1
	chkconfig crond on >> $LOG 2>&1

	# Check if perl available or not
	if ! type "perl" >> $LOG 2>&1; then
		yum -y install perl >> $LOG 2>&1
	fi

	# Check if unzip available or not
	if ! type "unzip" >> $LOG 2>&1; then
		yum -y install unzip >> $LOG 2>&1
	fi

	# Check if curl available or not
	if ! type "curl" >> $LOG 2>&1; then
		yum -y install curl >> $LOG 2>&1
	fi
fi

# Debian / Ubuntu
if [ -n "$(command -v apt-get)" ]; then
	apt-get update -y >> $LOG 2>&1
	apt-get install -y cron curl gzip openssl >> $LOG 2>&1
	service cron start >> $LOG 2>&1

	# Check if perl available or not
	if ! type "perl" >> $LOG 2>&1; then
		apt-get install -y perl >> $LOG 2>&1
	fi

	# Check if unzip available or not
	if ! type "unzip" >> $LOG 2>&1; then
		apt-get install -y unzip >> $LOG 2>&1
	fi

	# Check if curl available or not
	if ! type "curl" >> $LOG 2>&1; then
		apt-get install -y curl >> $LOG 2>&1
	fi
fi

# ArchLinux
if [ -n "$(command -v pacman)" ]; then
	pacman -Sy  >> $LOG 2>&1
	pacman -S --noconfirm cronie curl gzip openssl >> $LOG 2>&1
	systemctl start cronie >> $LOG 2>&1
	systemctl enable cronie >> $LOG 2>&1

	# Check if perl available or not
	if ! type "perl" >> $LOG 2>&1; then
		pacman -S --noconfirm perl >> $LOG 2>&1
	fi

	# Check if unzip available or not
	if ! type "unzip" >> $LOG 2>&1; then
		pacman -S --noconfirm unzip >> $LOG 2>&1
	fi

	# Check if curl available or not
	if ! type "curl" >> $LOG 2>&1; then
		pacman -S --noconfirm curl >> $LOG 2>&1
	fi
fi


# OpenSuse
if [ -n "$(command -v zypper)" ]; then
	zypper --non-interactive install cronie curl gzip openssl >> $LOG 2>&1
	service cron start >> $LOG 2>&1

	# Check if perl available or not
	if ! type "perl" >> $LOG 2>&1; then
		zypper --non-interactive install perl >> $LOG 2>&1
	fi

	# Check if unzip available or not
	if ! type "unzip" >> $LOG 2>&1; then
		zypper --non-interactive install unzip >> $LOG 2>&1
	fi

	# Check if curl available or not
	if ! type "curl" >> $LOG 2>&1; then
		zypper --non-interactive install curl >> $LOG 2>&1
	fi
fi


# Gentoo
if [ -n "$(command -v emerge)" ]; then

	# Check if crontab is present or not available or not
	if ! type "crontab" >> $LOG 2>&1; then
		emerge cronie >> $LOG 2>&1
		/etc/init.d/cronie start >> $LOG 2>&1
		rc-update add cronie default >> $LOG 2>&1
 	fi

	# Check if perl available or not
	if ! type "perl" >> $LOG 2>&1; then
		emerge perl >> $LOG 2>&1
	fi

	# Check if unzip available or not
	if ! type "unzip" >> $LOG 2>&1; then
		emerge unzip >> $LOG 2>&1
	fi

	# Check if curl available or not
	if ! type "curl" >> $LOG 2>&1; then
		emerge net-misc/curl >> $LOG 2>&1
	fi

	# Check if gzip available or not
	if ! type "gzip" >> $LOG 2>&1; then
		emerge gzip >> $LOG 2>&1
	fi
fi


# Slackware
if [ -f "/etc/slackware-version" ]; then

	if [ -n "$(command -v slackpkg)" ]; then

		# Check if crontab is present or not available or not
		if ! type "crontab" >> $LOG 2>&1; then
			slackpkg -dialog=off -batch=on -default_answer=y install dcron >> $LOG 2>&1
		fi

		# Check if perl available or not
		if ! type "perl" >> $LOG 2>&1; then
			slackpkg -dialog=off -batch=on -default_answer=y install perl >> $LOG 2>&1
		fi

		# Check if unzip available or not
		if ! type "unzip" >> $LOG 2>&1; then
			slackpkg -dialog=off -batch=on -default_answer=y install infozip >> $LOG 2>&1
		fi

		# Check if curl available or not
		if ! type "curl" >> $LOG 2>&1; then
			slackpkg -dialog=off -batch=on -default_answer=y install curl >> $LOG 2>&1
		fi

		# Check if gzip available or not
		if ! type "gzip" >> $LOG 2>&1; then
			slackpkg -dialog=off -batch=on -default_answer=y install gzip >> $LOG 2>&1
		fi

	else
		echo "Please install slackpkg and re-run installation."
		exit 1;
	fi
fi


# Is Cron available?
if [ ! -n "$(command -v crontab)" ]; then
	echo "Cron is required but we could not install it."
	echo "Exiting installer"
	exit 1;
fi

# Is CURL available?
if [  ! -n "$(command -v curl)" ]; then
	echo "CURL is required but we could not install it."
	echo "Exiting installer"
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

### Install ###
mkdir -p /opt/sentruo >> $LOG 2>&1
wget -N --no-check-certificate -O /opt/sentruo/agent.sh $2/assets/agent.sh >> $LOG 2>&1
wget -N --no-check-certificate -O /opt/sentruo/uninstall.sh $2/assets/uninstall.sh >> $LOG 2>&1

echo "$1" > /opt/sentruo/serverkey
echo "$2/agent.php" > /opt/sentruo/gateway

# Optional 3rd arg: deployment-wide HMAC secret. When omitted, the agent signs
# with the per-server key (still HMAC + timestamp + replay protection).
if [ -n "$3" ]; then
	echo "$3" > /opt/sentruo/hmac_secret
	chmod 600 /opt/sentruo/hmac_secret
fi

chmod 600 /opt/sentruo/serverkey

# Did it download ?
if ! [ -f /opt/sentruo/agent.sh ]; then
	echo "Unable to install!"
	echo "Exiting installer"
	exit 1;
fi

#useradd sentruo-agent -r -d /opt/sentruo -s /bin/false >> $LOG 2>&1
#groupadd sentruo-agent >> $LOG 2>&1
#usermod -a -G sudo sentruo-agent

crontab -r -u sentruo-agent >> $LOG 2>&1
userdel sentruo-agent >> $LOG 2>&1

# Disable cagefs for sentruo
if [ -f /usr/sbin/cagefsctl ]; then
	/usr/sbin/cagefsctl --disable sentruo-agent >> $LOG 2>&1
fi

# Modify user permissions
#chown -R sentruo-agent:sentruo-agent /opt/sentruo && chmod -R 700 /opt/sentruo >> $LOG 2>&1

# Configure cron
if ! crontab -u root -l | grep '* * * * * bash /opt/sentruo/agent.sh > /opt/sentruo/cron.log 2>&1' &> /dev/null
then
	crontab -u root -l 2>/dev/null | { cat; echo "* * * * * bash /opt/sentruo/agent.sh > /opt/sentruo/cron.log 2>&1"; } | crontab -u root -
fi



echo " "
echo "-------------------------------------"
echo " Installation Completed "
echo "-------------------------------------"


# Attempt to delete this installer
if [ -f $0 ]; then
	rm -f $0
fi
