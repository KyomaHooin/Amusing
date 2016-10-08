
RPI

<pre>
apt-get install imagemagick bc streamer watchdog

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

echo ds1307 0x68 > /sys/class/i2c-adapter/i2c-1/new_device
ip addr add 192.168.11.x dev eth0 2>/dev/null
/root/amusing/rpi-amusing.py &
/root/firewall &

/etc/network/intefaces:

iface wlan0 inet dhcp
	wpa-ssid nm-private
	wpa-psk *******************************************

/etc/udev/rules.d/23-usb-serial.rules:

SUBSYSTEM=="tty", ATTRS{idVendor}=="067b", ATTRS{idProduct}=="2303", SYMLINK+="AVR"

/etc/udev/rules.d/42-usb-cam.rules:

KERNEL=="video*", SUBSYSTEM=="video4linux", KERNELS=="1-1.4", SYMLINK+="video-cam0"
KERNEL=="video*", SUBSYSTEM=="video4linux", KERNELS=="1-1.5", SYMLINK+="video-cam1"

udevadm trigger

/etc/modules:

i2c-dev
rtc_ds1307
bcm2708_wdog

/etc/watchdog.conf:

watchdog-device = /dev/watchdog

update-rc.d watchdog enable
</pre>

FILE

<pre>
rpi-amusing.py - AVR program.
cam-amusing.sh - USB camera program.
</pre>

CONTACT

Author: richard_bruna@nm.cz<br>
Source: https://github.com/KyomaHooin/Amusing

