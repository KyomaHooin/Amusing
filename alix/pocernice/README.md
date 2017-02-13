
DESCRIPTION

Data transport GZIP compressed CSV over HTTP.

LABJACK
<pre>
- U3-HV
- FirmwareVersion: 1.32
- HardwareVersion: 1.30
- BootloaderVersion: 0.27
- EIO0-EIO7 = AIN8-AIN15
</pre>
SENSOR
<pre>
- 5 x Comet T0210

pocernice_07 - H: CH01 T: CH03
pocernice_08 - H: CH07
pocernice_09 - H: CH02 T: CH08
pocernice_10 - H: CH05
pocernice_11 - H: CH06 T: CH04
</pre>
ALIX
<pre>
apt-get install watchdog xinetd autossh

mkdir -p /root/amusing/ramdisk

/etc/fstab:

tmpfs	/root/amusing/ramdisk	tmpfs	nodev,nosuid,size=32M	0	0

/etc/services:

pocernice	8889/tcp			# prenos dat z Pocernic

/etc/xinet.d/pocernice:

service pocernice
{
        flags           = REUSE
        socket_type     = stream
        wait            = no
        user            = root
        server          = /usr/local/bin/getvalues8
        log_on_failure  += USERID
        disable         = no
}

/etc/crontab:

*/5 *	* * *	root	/usr/sbin/ntpdate -4 tik.cesnet.cz > /dev/null 2>&1

/etc/rc.local:

/usr/sbin/ntpdate -b -4 tik.cesnet.cz > /dev/null 2>&1 &
/root/firewall &
/root/tunnel &

/etc/watchdog:

watchdog-device = /dev/watchdog
interval = 15
</pre>

FILE
<pre>
                          tunnel - AutoSSH tunnel.
                        firewall - Simple firewall.
                 alix-amusing.py - Main data transport.
               LabJackPython.zip - LabJack Python library by LabJack (c) 2015
labjack-exodriver-4a45f5f.tar.gz - LabJack U3 driver v2.5.1-0-g by LabJack (c) 2009.
</pre>

CONTACT

Author: richard_bruna@nm.cz<br>
Source: https://github.com/KyomaHooin/Amusing
