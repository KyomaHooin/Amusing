
DESCRIPTION

Collect 868Mhz AVR RF sensor temperature/humidity data and transport GZIP compressed CSV over HTTP.

INSTALL

<pre>
mkdir /root/amusing
mount -t tmpfs -o size=32m tmpfs /root/amusing/ramdisk

/etc/crontab:

tmpfs /root/amusing/ramdisk   tmpfs   nodev,nosuid,size=32M   0       0

/etc/rc.local:

/root/amusing/rpi-amusing.py &
</pre>

FILE

<pre>
rpi-amusing.py - Main program.
</pre>

CONTACT

Author: richard_bruna@nm.cz<br>
Source: https://github.com/KyomaHooin/Amusing

