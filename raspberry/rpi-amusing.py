#!/usr/bin/python
#
# Transport GZIP serial data over HTTP.
#
# RAMDISK:
#
# mount -t tmpfs -o size=32m tmpfs /root/amusing/ramdisk
#
# DATA:
#
#               'Got msg #311 : *ZF#T273H370L000B467'
#                                .  .   .   .__ Serial numberor longint timestamp??
#                                .  .   .______ Humidity
#                                .  .__________ Temperature
#                                ._____________ Local ID
#

import httplib
import serial
import socket
import time
import gzip
import os
import re

LOCATION='archa'
RAMDISK='/root/amusing/ramdisk/'
SENSOR_FILE='/root/amusing/rpi-sensor.txt'

try:
	try:
		# DIR
		os.mkdir(RAMDISK + 'archive')
		os.mkdir(RAMDISK + 'http')
	except OSError: pass
	
	try:
		# LOG
		LOG = open(RAMDISK + 'rpi-amusing.log','a',0)# 0 -> non-buffering
		LOG.write('Program start: ' + time.strftime("%d.%m.%Y %H:%M") + '\n')
		#GZIP
		#CSV=LOCATION + '-' + time.strftime("%Y%m%dT%H%M%S") + '.csv.gz'
	       	#GZ=gzip.open(RAMDISK + 'http/' + CSV, 'ab')
		GZ=gzip.open(RAMDISK + 'http/' + LOCATION + '-' + time.strftime("%Y%m%dT%H%M%S") + '.csv.gz', 'ab')
	except IOError:
		print 'Failed to create stream.'
		exit(1)

#	MAIN
	while 1:
#		#SERIAL
		s = serial.Serial('/dev/ttyUSB0',9600,timeout=1)# 8,N,1 [default]
		data = s.readline()
		if data != '':
			print data, time.strftime("%H%M")
			if re.match('^Got',data):# filter rubbish..
				#print re.sub('^.* \*([A-Z]{2})#T(\d\d)(\d)H(\d\d)(\d)(.*)$',
				#	'\\6\;foo', data),
				print re.sub('^.* \*([A-Z]{2})#T(\d\d)(\d)H(\d\d)(\d)(.*)$',
					'\\6' + ';temperature;\\2.\\3;' + time.strftime("%Y%m%dT%H%M%S"), data),
				print re.sub('^.* \*([A-Z]{2})#T(\d\d)(\d)H(\d\d)(\d)(.*)$',
					'\\6;humidity;\\4.\\5;' + time.strftime("%Y%m%dT%H%M%S"), data),
				#GZ.write(re.sub('^.* \*([A-Z]{2})#T(\d\d)(\d)H(\d\d)(\d)(.*)$',),
				#	'\\6;temperature;\\2.\\3;' + time.strftime("%Y%m%dT%H%M%S"), data))
				#GZ.write(re.sub('^.* \*([A-Z]{2})#T(\d\d)(\d)H(\d\d)(\d)(.*)$',),
				#	'\\6;humidity;\\4.\\5;' + time.strftime("%Y%m%dT%H%M%S"), data))
		s.close()

		print 'Nothing..'

#       	gzip.open(RAMDISK + 'http/' + f, 'wb').write(data)
#		gz=open(RAMDISK + 'http/' + f,'rb')

#		if time.strftime("%M") == '45': #every  hour send the pack..
#			try:
#				HEADER={'Content-type':'application/octet-stream',
#					'X-Location':LOCATION + time.strftime("%Y%m%dT%H%M%S")}
#				c=httplib.HTTPConnection('amusing.nm.cz', '80', timeout=10)
#				c.request('POST', 'http://amusing.nm.cz/sensors/rawpost.php', gz, header)
#				r=c.getresponse()
#				if (r.status == 200):
#					print "ok"
#				gz.close()
#				c.close()
#			except socket.error:
#				LOG.write('Socket error HTTP transport failed.')
		#ARCHIVE
		#os.rename('http/' + f,'archive/' + f)

		#HTTP[socket.error]

#		c.request('POST', '[removed]', gz, header)
#		r=c.getresponse()
#		if (r.status == 200):
#			print "ok"
#		gz.close()
#		c.close()
except Exception as e:
	print e.args[0]
	exit(99)
LOG.write('Program end.\n')
LOG.close()
