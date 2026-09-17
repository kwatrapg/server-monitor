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
	yum -y install cronie gzip curl openssl jq >> $LOG 2>&1
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
	apt-get install -y cron curl gzip openssl jq >> $LOG 2>&1
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
	pacman -S --noconfirm cronie curl gzip openssl jq >> $LOG 2>&1
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
	zypper --non-interactive install cronie curl gzip openssl jq >> $LOG 2>&1
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



##################################
###   LOG SHIPPING (ALLOY)     ###
##################################
# Optional and idempotent. The Loki push URL and per-server token are never
# passed as installer arguments - they are generated server-side and pulled
# from agentconfig.php, the same way agent.sh only ever knows its serverkey
# and gateway. Metrics collection above is already fully configured by this
# point, so anything that goes wrong here is logged and skipped, never fatal.

echo "Installing log shipper (optional)"

if [ -n "$(command -v systemctl)" ]; then

	ALLOY_VERSION="1.18.1"
	ALLOY_ARCH=""
	case "$(uname -m)" in
		aarch64|arm64) ALLOY_ARCH="arm64" ;;
		x86_64|amd64)  ALLOY_ARCH="amd64" ;;
	esac

	if [ -n "$ALLOY_ARCH" ]; then

		mkdir -p /opt/sentruo/alloy/data >> $LOG 2>&1

		# Idempotent: only download/extract if not already present
		if [ ! -x /opt/sentruo/alloy/alloy ]; then
			curl -m 120 -sL -k -o /tmp/sentruo-alloy.zip \
				"https://github.com/grafana/alloy/releases/download/v${ALLOY_VERSION}/alloy-linux-${ALLOY_ARCH}.zip" >> $LOG 2>&1
			unzip -o -q /tmp/sentruo-alloy.zip -d /opt/sentruo/alloy >> $LOG 2>&1
			mv -f "/opt/sentruo/alloy/alloy-linux-${ALLOY_ARCH}" /opt/sentruo/alloy/alloy >> $LOG 2>&1
			chmod +x /opt/sentruo/alloy/alloy >> $LOG 2>&1
			rm -f /tmp/sentruo-alloy.zip >> $LOG 2>&1
		fi

		if [ -x /opt/sentruo/alloy/alloy ]; then

			# Initial config - alloy-config-sync.sh (installed below) keeps this current from here on
			curl -m 50 -k -s -o /opt/sentruo/alloy/config.river "$2/agentconfig.php?serverkey=$1" >> $LOG 2>&1

			cat > /opt/sentruo/alloy-config-sync.sh << 'SYNCEOF'
#!/bin/bash
# Polls agentconfig.php for this server's log-shipping config and hot-reloads
# Alloy only when it actually changed (ETag/If-None-Match). Run from cron -
# deliberately not a long-lived process, matching this app's existing agent
# model of stateless periodic checks rather than another daemon to babysit.

GATEWAY=$(cat /opt/sentruo/gateway | sed 's#/agent\.php$##')
SERVERKEY=$(cat /opt/sentruo/serverkey)
ETAGFILE=/opt/sentruo/alloy/config.etag
CONFIGFILE=/opt/sentruo/alloy/config.river

ETAG=""
if [ -f "$ETAGFILE" ]; then ETAG=$(cat "$ETAGFILE"); fi

HTTPCODE=$(curl -m 50 -k -s -o /tmp/sentruo-alloy-config.new -w "%{http_code}" \
	-H "If-None-Match: $ETAG" -D /tmp/sentruo-alloy-config.headers \
	"$GATEWAY/agentconfig.php?serverkey=$SERVERKEY")

if [ "$HTTPCODE" = "200" ]; then
	mv -f /tmp/sentruo-alloy-config.new "$CONFIGFILE"
	grep -i '^ETag:' /tmp/sentruo-alloy-config.headers | sed -e 's/^[Ee][Tt]ag: *//' -e 's/\r$//' > "$ETAGFILE"
	# Hot reload first (keeps file-tailing offsets); fall back to a full restart
	# if the endpoint isn't reachable, e.g. right after first install.
	curl -m 10 -s -X POST http://127.0.0.1:12345/-/reload > /dev/null 2>&1 || systemctl restart sentruo-alloy > /dev/null 2>&1
fi

rm -f /tmp/sentruo-alloy-config.new /tmp/sentruo-alloy-config.headers
SYNCEOF
			chmod +x /opt/sentruo/alloy-config-sync.sh >> $LOG 2>&1

			cat > /etc/systemd/system/sentruo-alloy.service << 'UNITEOF'
[Unit]
Description=Sentruo Log Shipper (Grafana Alloy)
After=network-online.target
Wants=network-online.target

[Service]
Type=simple
ExecStart=/opt/sentruo/alloy/alloy run --storage.path=/opt/sentruo/alloy/data /opt/sentruo/alloy/config.river
Restart=on-failure
RestartSec=5
User=root

[Install]
WantedBy=multi-user.target
UNITEOF

			systemctl daemon-reload >> $LOG 2>&1
			systemctl enable sentruo-alloy >> $LOG 2>&1
			systemctl restart sentruo-alloy >> $LOG 2>&1

			# Configure cron for the config-sync wrapper (idempotent, same pattern as the metrics cron line above)
			if ! crontab -u root -l 2>/dev/null | grep 'alloy-config-sync.sh' &> /dev/null
			then
				crontab -u root -l 2>/dev/null | { cat; echo "*/5 * * * * bash /opt/sentruo/alloy-config-sync.sh > /opt/sentruo/alloy-sync.log 2>&1"; } | crontab -u root -
			fi

			echo "Log shipper installed."
		else
			echo "Log shipper skipped: could not download Alloy."
		fi
	else
		echo "Log shipper skipped: unsupported CPU architecture ($(uname -m))."
	fi
else
	echo "Log shipper skipped: systemd not available on this system."
fi


echo " "
echo "-------------------------------------"
echo " Installation Completed "
echo "-------------------------------------"


# Attempt to delete this installer
if [ -f $0 ]; then
	rm -f $0
fi
