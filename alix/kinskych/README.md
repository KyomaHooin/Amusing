![Alix](https://github.com/KyomaHooin/Amusing/raw/master/alix/kinskych/kinskych_screen.png "screenshot")

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

/etc/crontab:

*/5 *	* * *	root	/usr/sbin/ntpdate -b -4 195.113.144.201 > /dev/null 2>&1

/etc/default/watchdog:

run_watchdog=0
run_wd_keepalive=0

update-rc.d watchdog disable

/etc/network/interfaces.d/eth0.conf:

auto eth0
iface eth0 inet static
        address 10.0.40.197
        netmask 255.255.255.0
        gateway 10.0.40.200
        dns-nameservers 10.0.1.187 10.0.1.197

/etc/rc.local:

/usr/sbin/ntpdate -b -4 195.113.144.201 > /dev/null 2>&1 &
/root/amusing/alix-amusing.py &
/root/firewall &
/root/tunnel &

remountro
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

4] watchdog

watchdog[2281]: cannot get watchdog identity (errno = 25 = 'Inappropriate ioctl for device')

</pre>
FILE
<pre>
    alix-amusing.py - Main data transport.
             tunnel - AutoSSH tunnel.
           firewall - Simple firewall.
kinskych_screen.png - HW screen.
</pre>

SOURCE

https://github.com/KyomaHooin/Amusing

