![Alix](https://github.com/KyomaHooin/Amusing/raw/master/alix/kinskych/kinskych_screen.png "screenshot")

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

/etc/network/interfaces.d/eth0.conf:

auto eth0
iface eth0 inet static
        address 10.0.40.197
	netmask 255.255.255.0
        gateway 10.0.40.200
        dns-nameservers 10.0.0.200

/etc/rc.local:

/usr/sbin/ntpdate -b -4 tik.cesnet.cz > /dev/null 2>&1 &
/root/amusing/alix-amusing.py &
/root/firewall &
/root/tunnel &
</pre>
BUG
<pre>
1] install
>>> Created a new DOS disklabel with disk identifier 0x4156d18d.
Start sector 0 out of range.
Failed to add partition: Numerical result out of range

2] boot
fsck: error 2 (No such file or directory) while executing fsck.ext2 for /dev/sda1
fsck exited with status code 8

3] locales

Generating locales (this might take a while)...
  en_US.UTF-8...memory exhausted(Killed)
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
