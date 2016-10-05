
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

/root/amusing/rpi-amusing.py &
/root/firewall &

/etc/udev/rules.d/42-usb-cam.rules:

KERNEL=="video*", SUBSYSTEM=="video4linux", KERNELS=="1-1.2", SYMLINK+="video-cam0"

udevadm trigger

/etc/modules:

bcm2708_wdog

/etc/watchdog.conf:

watchdog-device = /dev/watchdog

update-rc.d watchdog enable
</pre>

FILE

<pre>
rpi-amusing.py - DHT program.
cam-amusing.sh - USB camera program.
      getDHT.c - DHT22 source by Dom & Gert.
        getDHT - Precompiled ARM binary.
</pre>

CONTACT

Author: richard_bruna@nm.cz<br>
Source: https://github.com/KyomaHooin/Amusing

