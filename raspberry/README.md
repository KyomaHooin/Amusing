
DESCRIPTION

Collect 868Mhz AVR RF sensor temperature/humidity data and transport GZIP compressed CSV over HTTP.

INSTALL

<pre>
mkdir -p /root/amusing/ramdisk
mount -t tmpfs -o size=32m tmpfs /root/amusing/ramdisk

/etc/fstab:

tmpfs	/root/amusing/ramdisk   tmpfs   nodev,nosuid,size=32M   0       0

tmpfs	/tmp		tmpfs	defaults,noatime,nosuid,size=100m	0	0
tmpfs	/var/log	tmpfs	defaults,noatime,nosuid,mode=0755,size=100m	0	0
tmpfs	/var/run	tmpfs	defaults,noatime,nosuid,mode=0755,size=2m	0	0

/etc/rc.local:

/root/amusing/rpi-amusing.py &
/root/firewall &

/etc/network/intefaces:

iface wlan0 inet dhcp
	wpa-ssid nm-private
	wpa-psk ******************************************* <- wpa_passphrase
</pre>

FILE

<pre>
rpi-amusing.py - Main program.
      firewall - Simple restrictive firewall(performance issues).
</pre>

CONTACT

Author: richard_bruna@nm.cz<br>
Source: https://github.com/KyomaHooin/Amusing

