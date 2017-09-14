![Amusing](https://github.com/KyomaHooin/Amusing/raw/master/frontend/amusing_screen.png "screenshot")

DESCRIPTION

Amusing PHP frontned original code by Jan Klaban (c) 2012-2014 Peoplefornet a.s. 

ETC
<pre>
/etc/rc.local:

/root/firewall &
</pre>
CRON
<pre>
#Amusing - clean cached plot data [10/20min]
10 * * * * root	/usr/bin/find /var/www/muzeum/plot -type f -mtime +2 ! -name .htaccess -print0 | /usr/bin/xargs -0 /bin/rm -f
20 * * * * root	/usr/bin/find /var/www/muzeum/csv -type f -mtime +2 ! -name .htaccess -print0 | /usr/bin/xargs -0 /bin/rm -f

#Amusing - alarm scheduler [20min]
*/20 * * * *	root	/usr/bin/wget -t 1 -O /dev/null https://amusing.nm.cz/muzeum/cron > /dev/null 2>&1

#Amusing - RAW data processing [5min]
*/5 * * * *	root	/usr/bin/wget -t 1 -O /dev/null https://amusing.nm.cz/muzeum/cronraw > /dev/null 2>&1

#Amusing - RAW data archiving [3:00 AM] *.{done,err} no work..
0 3	* * *	root	/bin/gzip /var/www/sensors/data/*.done > /dev/null 2>&1
0 3	* * *	root	/bin/gzip /var/www/sensors/data/*.err > /dev/null 2>&1

#Amusing - RAW data cleanup [2:00 AM]
0 2 * * * root /usr/bin/find /var/www/sensors/data -type f -name "*.gz" -mtime +7 -print0 | /usr/bin/xargs -0 /bin/rm -f

#Amusing Report[1st dom at 8:30]
30 8 1 * *	root	/root/report 1M &
30 8 * * 1     root    [ $(expr `date +\%W` \% 2) -eq 0 ] && /root/report 2W &
30 8 * * 1     root    /root/report 7D &
30 8 * * *     root    /root/report 1D &

#Amusing Datalogger[8:35]
35 10 * * *	root	/root/logger &
</pre>
APACHE
<pre>
a2enmod rewrite ssl headers
</pre>
MYSQL
<pre>
mysql-server

create database xxx charset utf8;
create user 'yyy' identified by 'zzz';
grant all privileges on xxx.* to 'yyy'@'localhost';
</pre>
PHP
<pre>
php5 php5-gd php5-curl php5-imagic php5-recode php5-mysql php5-apcu php-apc php5-ldap
</pre>
EXTRA
<pre>
gnuplot gnuplot-nox imagemagick python python-reportlab
</pre>
FILE
<pre>
           muzeum/ - PHP Frontend.
          sensors/ - Sensor interface.
            logger - Python logger.
            report - Python report.
            backup - Shell backup.
          firewall - Shell firewall.
     templeate.sql - Empty MySQL structure.
amusing_screen.png - UI screenshot. 
</pre>

CONTACT

Author: richard_bruna@nm.cz<br>
Source: https://github.com/KyomaHooin/Amusing

