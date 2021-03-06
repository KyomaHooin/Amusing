![RPi](https://github.com/KyomaHooin/Amusing/raw/master/raspberry/archa/archa_screen.png "screenshot")

DESCRIPTION

Read AVR 868Mhz receiver data with PL2303 and trasport GZIP data over HTTP.

SENSOR
<pre>
E,F,G,H - 3 x AAA 1.5V battery
</pre>
RPI
<pre>
apt-get install imagemagick bc streamer watchdog

mkdir -p /root/amusing/ramdisk
mount -t tmpfs -o size=32m tmpfs /root/amusing/ramdisk

/etc/crontab:

40 23	* * *	root	/root/amusing/cam-amusing.sh > /dev/null 2>&1
*/10 *	* * *	root	[ $(cat /sys/class/net/wlan0/operstate) = 'down' ] && ifup wlan0 > /dev/null 2>&1

/etc/fstab:

tmpfs	/root/amusing/ramdisk   tmpfs   nodev,nosuid,size=32M   0       0

tmpfs	/tmp		tmpfs	defaults,noatime,nosuid,size=100m	0	0
tmpfs	/var/log	tmpfs	defaults,noatime,nosuid,mode=0755,size=100m	0	0
tmpfs	/var/run	tmpfs	defaults,noatime,nosuid,mode=0755,size=2m	0	0

/etc/rc.local:

echo ds1307 0x68 > /sys/class/i2c-adapter/i2c-1/new_device
ip addr add 192.168.11.11(12)/24 dev eth0 2>/dev/null &
/root/amusing/rpi-amusing.py &
/root/firewall &

/etc/network/intefaces:

auto eth0
allow-hotplug eth0
iface eth0 inet static
	address 192.168.11.11(12)
	netmask 255.255.255.0

auto wlan0
allow-hotplug wlan0
iface wlan0 inet static
	address 10.10.8.65(66)
	netmask 255.255.0.0
	gateway 10.10.10.43
	dns-nameservers 10.10.9.26 10.10.9.27
	wpa-ssid nm-private
	wpa-psk *******************************************
	wireless-power off

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
watchdog-timeout = 15

update-rc.d watchdog enable
</pre>

BUG
<pre>
ioctl[SIOCSIWAP]: Operation not permitted
ioctl[SIOCSIWENCODEEXT]: Invalid argument
ioctl[SIOCSIWENCODEEXT]: Invalid argument
</pre>

FILE

<pre>
          avr/ - AVR 868 RF & PWM code.

rpi-amusing.py - AVR program.
cam-amusing.sh - USB camera program.
 raspberry.png - HW internals.
      firewall - Simple firewall.
</pre>

SOURCE

https://github.com/KyomaHooin/Amusing

