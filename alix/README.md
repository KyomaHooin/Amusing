
DESCRIPTION

Alix USB camera data transport GZIP compressed CSV over HTTP.

ALIX
<pre>
apt-get install bc imagemagick

mkdir -p /root/amusing/ramdisk

/etc/fstab:

tmpfs	/root/amusing/ramdisk	tmpfs	nodev,nosuid,size=32M	0	0

/etc/crontab:

40 23	* * *	root	/root/amusing/cam-amusing.sh > /dev/null 2>&1

/etc/rc.local:

/root/firewall &

modrpobe cs5535-gpio

/etc/modules:

cs5535-gpio
</pre>

FILE

<pre>
     cam-amusing.sh - Main script.
</pre>

CONTACT

Author: richard_bruna@nm.cz<br>
Source: https://github.com/KyomaHooin/Amusing
