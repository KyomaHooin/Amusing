![Alix](https://github.com/KyomaHooin/Amusing/raw/master/alix/nostic/kinskych_screen.png "screenshot")

DESCRIPTION

Alix RS232 connected AVR 868MHz DH11 sensor data transport GZIP compressed CSV over HTTP.

WRAP
<pre>
apt-get install wget vim locales autossh ntpdate python python-serial

mkdir -p /root/amusing/ramdisk

/etc/inittab:

T0:3:respawn:/sbin/getty -L ttyS0 38400

/etc/fstab:

tmpfs	/root/amusing/ramdisk	tmpfs	nodev,nosuid,size=32M	0	0

/etc/crontab:

*/5 *	* * *	root	/usr/sbin/ntpdate -4 tik.cesnet.cz > /dev/null 2>&1

/etc/rc.local:

/usr/sbin/ntpdate -b -4 tik.cesnet.cz > /dev/null 2>&1 &
/root/amusing/alix-amusing.py &
/root/firewall &
/root/tunnel &
</pre>

FILE
<pre>
    alix-amusing.py - Main data transport.
             tunnel - AutoSSH tunnel.
           firewall - Simple firewall.
kinskych_screen.png - HW screen.
</pre>

CONTACT

Author: richard_bruna@nm.cz<br>
Source: https://github.com/KyomaHooin/Amusing
