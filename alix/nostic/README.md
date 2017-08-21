![Alix](https://github.com/KyomaHooin/Amusing/raw/master/alix/nostic/nostic_screen.png "screenshot")

DESCRIPTION

Alix RS232 connected AVR 868MHz DH22 sensor data transport GZIP compressed CSV over HTTP.

SENSOR
<pre>
C - 3 x N/LR1 1.5V battery
</pre>
WRAP
<pre>
apt-get install wget vim locales autossh ntpdate python python-serial

mkdir -p /root/amusing/ramdisk

/etc/inittab:

T0:3:respawn:/sbin/getty -L ttyS0 38400

/etc/fstab:

tmpfs	/root/amusing/ramdisk	tmpfs	nodev,nosuid,size=32M	0	0

/etc/watchdog.conf:

watchdog-device = /dev/watchdog
interval = 15

/etc/crontab:

*/5 *	* * *	root	/usr/sbin/ntpdate -b -4 172.16.64.1 > /dev/null 2>&1

/etc/network/interfaces.d/eth0.conf:

auto eth0
iface eth0 inet dhcp

/etc/rc.local:

/usr/sbin/ntpdate -b -4 172.16.64.1 > /dev/null 2>&1 &
/root/amusing/alix-amusing.py &
/root/firewall &
/root/tunnel &
</pre>

FILE
<pre>
                       tunnel - AutoSSH tunnel.
                     firewall - Simple firewall.
              alix-amusing.py - Main data transport.
            nostic-screen.png - HW screen.
</pre>

CONTACT

Author: richard_bruna@nm.cz<br>
Source: https://github.com/KyomaHooin/Amusing
