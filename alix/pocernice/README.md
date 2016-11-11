
DESCRIPTION

Alix data transport GZIP compressed CSV over HTTP.

ALIX
<pre>
apt-get install watchdog xinetd autossh

mkdir -p /root/amusing/ramdisk

/etc/fstab:

tmpfs	/root/amusing/ramdisk	tmpfs	nodev,nosuid,size=32M	0	0

/etc/services:

pocernice	8889/tcp			# prenos dat z Pocernic

/etc/xinet.d/pocernice:

service pocernice
{
        flags           = REUSE
        socket_type     = stream
        wait            = no
        user            = root
        server          = /usr/local/bin/getvalues8
        log_on_failure  += USERID
        disable         = no
}

/etc/crontab:

00 *	* * *	root	/usr/sbin/ntpdate -4 tik.cesnet.cz > /dev/null 2>&1

/etc/rc.local:

/usr/sbin/ntpdate -b -4 tik.cesnet.cz > /dev/null 2>&1 &
/root/firewall &
/root/tunnel &

/etc/watchdog:

watchdog-device = /dev/watchdog
interval = 15
</pre>

FILE
<pre>
      firewall - Simple firewall.
</pre>

CONTACT

Author: richard_bruna@nm.cz<br>
Source: https://github.com/KyomaHooin/Amusing