
DESCRIPTION

Alix data transport GZIP compressed CSV over HTTP.

ALIX
<pre>
apt-get install watchdog autossh

mkdir -p /root/amusing/ramdisk

/etc/inittab:

T0:3:respawn:/sbin/getty -L ttyS0 38400

/etc/fstab:

tmpfs	/root/amusing/ramdisk	tmpfs	nodev,nosuid,size=32M	0	0

/etc/crontab:

00 *	* * *	root	/usr/sbin/ntpdate -4 tik.cesnet.cz > /dev/null 2>&1

/etc/rc.local:

/usr/sbin/ntpdate -b -4 tik.cesnet.cz > /dev/null 2>&1 &
/root/amusing/alix-amusing.py &
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
