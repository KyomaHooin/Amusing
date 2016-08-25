
DESCRIPTION

Collect 868Mhz AVR RF sensor temperature/humidity & camera data and transport GZIP compressed CSV over HTTP.

INSTALL

<pre>
apt-get install imagemagick bc streamer

mkdir -p /root/amusing/ramdisk
mount -t tmpfs -o size=32m tmpfs /root/amusing/ramdisk

/etc/crontab:

40 23	* * *	root	/root/amusing/cam-amusing.sh > /dev/null 2>&1

/etc/fstab:

tmpfs	/root/amusing/ramdisk   tmpfs   nodev,nosuid,size=32M   0       0

tmpfs	/tmp		tmpfs	defaults,noatime,nosuid,size=100m	0	0
tmpfs	/var/log	tmpfs	defaults,noatime,nosuid,mode=0755,size=100m	0	0
tmpfs	/var/run	tmpfs	defaults,noatime,nosuid,mode=0755,size=2m	0	0

/etc/rc.local:

ip addr add 192.168.11.x dev eth0 2>/dev/null
/root/amusing/rpi-amusing.py &
/root/firewall &

/etc/network/intefaces:

iface wlan0 inet dhcp
	wpa-ssid nm-private
	wpa-psk *******************************************

/etc/udev/rules.d/23-usb-serial.rules:

SUBSYSTEM=="tty", ATTRS{idVendor}=="067b", ATTRS{idProduct}=="2303", SYMLINK+="AVR"

udevadm trigger
</pre>

FILE

<pre>
rpi-amusing.py - Main program.
cam-amusing.sh - USB camera program.
      firewall - Simple restrictive firewall(performance issues).
</pre>

CONTACT

Author: richard_bruna@nm.cz<br>
Source: https://github.com/KyomaHooin/Amusing

