![Amusing](https://github.com/KyomaHooin/Amusing/raw/master/frontend/amusing_screen.png "screenshot")

DESCRIPTION

Amusing PHP frontned (c) 2012-2014 Jan Klaban Peoplefornet a.s. 

RUNLEVEL
<pre>
ln -s /lib/systemd/system/multi-user.target /etc/systemd/system/default.target
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
php5 php5-gd php5-curl php5-imagic php5-recode php5-mysql php5-apcu php-apc
</pre>
EXTRA
<pre>
gnuplot gnuplot-nox imagemagick
</pre>
FILE
<pre>
           muzeum/ - PHP Frontend.
          sensors/ - Sensor interface.
     templeate.sql - Empty MySQL structure.
amusing_screen.png - UI screenshot. 
</pre>

CONTACT

Author: richard_bruna@nm.cz<br>
Source: https://github.com/KyomaHooin/Amusing

