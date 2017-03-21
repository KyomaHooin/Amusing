![RPi](https://github.com/KyomaHooin/Amusing/raw/master/raspberry/svycarna/svycarna_screen.png "screenshot")

RPI

<pre>
apt-get install imagemagick bc streamer watchdog

gpio:

<a href="http://wiringpi.com">WiringPi</a>

mkdir -p /root/amusing/ramdisk
mount -t tmpfs -o size=32m tmpfs /root/amusing/ramdisk

/etc/crontab:

40 23	* * *	root	/root/amusing/cam-amusing.sh > /dev/null 2>&1

/etc/fstab:

tmpfs	/root/amusing/ramdisk   tmpfs   nodev,nosuid,size=32M   0       0

tmpfs	/tmp		tmpfs	defaults,noatime,nosuid,size=100m	0	0
tmpfs	/var/log	tmpfs	defaults,noatime,nosuid,mode=0755,size=100m	0	0
tmpfs	/var/run	tmpfs	defaults,noatime,nosuid,mode=0755,size=2m	0	0

/etc/udev/rules.d/42-usb-cam.rules:

KERNEL=="video*", SUBSYSTEM=="video4linux", KERNELS=="1-1.2", SYMLINK+="video-cam0"

/etc/network/interfaces:

auto eth0
allow-hotplug eth0
iface eth0 inet static
    address 10.14.8.21
    netmask 255.255.0.0
    gateway 10.14.0.1
    network 10.14.0.0
    broadcast 10.14.255.255
    dns-nameservers 10.14.9.26

/etc/rc.local:

/root/amusing/rpi-amusing.py &
/root/firewall &

/etc/modules:

bcm2708_wdog

/etc/watchdog.conf:

watchdog-device = /dev/watchdog
watchdog-timeout = 15

update-rc.d watchdog enable
</pre>

FILE

<pre>
     rpi-amusing.py - DHT program.
     cam-amusing.sh - USB camera program.
           getDHT.c - DHT22 source by Dom & Gert.
             getDHT - Precompiled ARM binary.
bcm2835-1.36.tar.gz - BCM2835 library.
</pre>

CONTACT

Author: richard_bruna@nm.cz<br>
Source: https://github.com/KyomaHooin/Amusing

