![Alix](https://github.com/KyomaHooin/Amusing/raw/master/alix/umichala/umichala_screen.png "screenshot")

DESCRIPTION

Alix USB camera data transport GZIP compressed CSV over HTTP.

ALIX
<pre>
apt-get install bc imagemagick streamer watchdog

mkdir -p /root/amusing/ramdisk

/etc/fstab:

tmpfs	/root/amusing/ramdisk	tmpfs	nodev,nosuid,size=32M	0	0

/etc/crontab:

40 23	* * *	root	/root/amusing/cam-amusing.sh > /dev/null 2>&1
*/5 *	* * *	root	/usr/sbin/ntpdate -b -4 tik.cesnet.cz > /dev/null 2>&1

/etc/rc.local:

/usr/sbin/ntpdate -b -4 tik.cesnet.cz > /dev/null 2>&1 &
ip addr add 192.168.11.x/24 dev eth0 2>/dev/null &
/root/firewall &
/root/tunnel &

modrpobe cs5535-gpio

/etc/modules:

cs5535-gpio

/etc/watchdog:

watchdog-device = /dev/watchdog
interval = 15
</pre>

FILE

<pre>
     cam-amusing.sh - Main script.
           firewall - Simple firewall.
umichala_screen.png - HW screenshot.
</pre>

CONTACT

Author: richard_bruna@nm.cz<br>
Source: https://github.com/KyomaHooin/Amusing
