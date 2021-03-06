![Alix](https://github.com/KyomaHooin/Amusing/raw/master/alix/pocernice/pocernice_screen.png "screenshot")

DESCRIPTION

Read regulated Comet T0210 Vref with LabJack ADC and trasport calculated GZIP data over HTTP.

LABJACK
<pre>
- U3-HV
- FirmwareVersion: 1.32
- HardwareVersion: 1.30
- BootloaderVersion: 0.27
- EIO0-EIO7 = AIN8-AIN15

- 330k | 82k Voltage Divider
</pre>
SENSOR
<pre>
- 5 x Comet T0210

- 0-10V = 0-100%RH
- 0-10V = -30-80C

pocernice_07 - H: CH01 T: CH03
pocernice_08 - H: CH07
pocernice_09 - H: CH02 T: CH08
pocernice_10 - H: CH05
pocernice_11 - H: CH06 T: CH04
</pre>
ALIX
<pre>
apt-get install watchdog python ntpdate

mkdir -p /root/amusing/ramdisk

/etc/fstab:

tmpfs	/root/amusing/ramdisk	tmpfs	nodev,nosuid,size=32M	0	0

/etc/crontab:

*/5 *	* * *	root	/usr/sbin/ntpdate -b -4 tik.cesnet.cz > /dev/null 2>&1

/etc/network/interfaces:

auto eth0
iface eth0 inet static
        address 10.11.6.80
        netmask 255.255.0.0
        gateway 10.11.0.1
        dns-nameservers 10.10.9.30 8.8.8.8

/etc/rc.local:

/usr/sbin/ntpdate -b -4 tik.cesnet.cz > /dev/null 2>&1 &
/root/amusing/alix-amusing.py &
/root/firewall &

/etc/watchdog:

watchdog-device = /dev/watchdog
interval = 15

remountro
</pre>

FILE
<pre>
                        firewall - Simple firewall.
                   restore-u3.py - Restore U3 configuration.
                 alix-amusing.py - Main data transport.
               LabJackPython.zip - LabJack Python library by LabJack (c) 2015
labjack-exodriver-4a45f5f.tar.gz - LabJack U3 driver v2.5.1-0-g by LabJack (c) 2009.
</pre>

SOURCE

https://github.com/KyomaHooin/Amusing

