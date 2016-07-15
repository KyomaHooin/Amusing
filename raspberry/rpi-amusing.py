#!/usr/bin/python
#
# Transport GZIP serial data over HTTP.
#
# RAMDISK:
#
# tmpfs	/root/amusing/ramdisk	tmpfs	nodev,nosuid,size=32M	0	0
# mount -t tmpfs -o size=32m tmpfs /root/amusing/ramdisk
#
# DATA[5min]:
#
#               'Got msg #311 : *ZF#T273H370L000B467'
#                                .  .   .   .__ Serial/timestamp[?]
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
PAYLOAD=''
RAMDISK='/root/amusing/ramdisk/'

try:
	try:	# DIR
		os.mkdir(RAMDISK + 'archive')
		os.mkdir(RAMDISK + 'http')
	except OSError: pass
	try:	# LOG
		LOG = open(RAMDISK + 'rpi-amusing.log','a',0)# non-buffering
		LOG.write('Program start: ' + time.strftime("%d.%m.%Y %H:%M") + '\n')
	except IOError:
		print 'Fail to create log file.'
		exit(1)
	while 1:# SERIAL
		try:
			s = serial.Serial('/dev/ttyUSB0',9600,timeout=5)# 8,N,1; 5s scan..
			data = s.readline()
			if data != '':
				print data, 
				pattern = re.compile('^.* \*.(.)#T(\d\d)(\d)H(\d\d)(\d)(.*)$')
				if re.match(pattern, data):# rubbish..
					print re.sub(pattern,'\\1;temperature;\\2.\\3;'
						+ time.strftime("%Y%m%dT%H%M%S"), data),
					print re.sub(pattern,'\\1;humidity;\\4.\\5;'
						+ time.strftime("%Y%m%dT%H%M%S"), data),
					PAYLOAD+=(re.sub(pattern,'\\1;temperature;\\2.\\3;'
						+ time.strftime("%Y%m%dT%H%M%S"), data)
						+ re.sub(pattern,'\\1;humidity;\\4.\\5;'
						+ time.strftime("%Y%m%dT%H%M%S"), data))
			s.close()
		except serial.SerialException:
			LOG.write('Serial error.')
			pass
#		if time.strftime("%M") == '20': #hourly..
#			try:	# GZIP
#				GZIP_FILE=RAMDISK + 'http/' + LOCATION + '-' + time.strftime("%Y%m%dT%H%M%S") + '.csv.gz'
#				gzip.open(GZIP_FILE, 'ab').write(PAYLOAD)
#				GZ=open(GZIP_FILE, 'rb')
#				print('Payload ready..')
#			except IOError:
#				LOG.write('Fail to gzip payload.')
#				pass
#		#for pack in os.listdir(RAMDISK + http):
#			try:	# HTTP
#				HEADER={'Content-type':'application/octet-stream',
#					'X-Location':LOCATION + '-' + time.strftime("%Y%m%dT%H%M%S")}
#				c=httplib.HTTPConnection('amusing.nm.cz', '80', timeout=10)
#				c.request('POST', 'http://amusing.nm.cz/sensors/rawpost.php', GZ, HEADER)
#				r=c.getresponse()
#				if (r.status == 200):
#					print "Ok!"
#				c.close()
#			except socket.error:
#				LOG.write('Socket error HTTP transport failed.')
#				GZ.close()
#				pass
#			try:	# CLEANUP
#				GZ.close()
#				os.rename('http/' + GZIP_FILE,'archive/' + GZIP_FILE)
#				PAYLOAD=''
#			except OSError:
#				print('Allready sent..')# pass ..
#				#LOG.write('Fail to archive payload.')
except Exception as e:
	print e.args[0]
	exit(99)
LOG.write('Program end.\n')
LOG.close()
