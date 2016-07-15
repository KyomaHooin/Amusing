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
CALL=True

try:
	try:	# DIR
		os.mkdir(RAMDISK + 'archive')
		os.mkdir(RAMDISK + 'http')
	except OSError: pass
	try:	# LOG
		LOG = open(RAMDISK + 'rpi-amusing.log','a',0)# non-buffering
		LOG.write('Program start: ' + time.strftime("%d.%m.%Y %H:%M") + '\n')
	except IOError:
		print 'Failed to create log file.'
		exit(1)
	while 1:
		try:	# SERIAL
			s = serial.Serial('/dev/ttyUSB0',9600,timeout=5)# 8,N,1; 5s scan..
			data = s.readline()
			if data != '':
				pattern = re.compile('^.* \*.(.)#T(\d\d)(\d)H(\d\d)(\d).*$')
				if re.match(pattern, data):# rubbish..
					PAYLOAD+=(re.sub(pattern,'\\1;temperature;\\2.\\3;'
						+ time.strftime("%Y%m%dT%H%M%S"), data)
						+ re.sub(pattern,'\\1;humidity;\\4.\\5;'
						+ time.strftime("%Y%m%dT%H%M%S"), data))
			s.close()
		except serial.SerialException:
			LOG.write('Serial error.\n')
			pass
		if time.strftime("%M") == '50' and CALL: #hourly..
			CALL=False
			try:	# GZIP + PAYLOAD
				GZIP_FILE=RAMDISK + 'http/' + LOCATION + '-' + time.strftime("%Y%m%dT%H%M%S") + '.csv.gz'
				gzip.open(GZIP_FILE, 'ab').write(PAYLOAD)
			except IOError:
				LOG.write('Failed to gzip payload.\n')
				pass
			for PACK in os.listdir(RAMDISK + 'http'):
				try:
					GZIP=open(RAMDISK + 'http/' + PACK, 'rb')
				except IOError:
					LOG.write('Fail to read ' + PACK + '.\n')
					pass
				try:	# HTTP
					HEADER={'Content-type':'application/octet-stream',
						'X-Location':LOCATION + '-' + time.strftime("%Y%m%dT%H%M%S")}
					c=httplib.HTTPConnection('amusing.nm.cz', '80', timeout=10)
					c.request('POST', 'http://amusing.nm.cz/sensors/rawpost.php', GZIP, HEADER)
					r=c.getresponse()
					if (r.status == 200):
						try:	# ARCHIVE
							os.rename(RAMDISK + 'http/' + PACK, RAMDISK + 'archive/' + PACK)
						except OSError:
							LOG.write('Nothing to archive.\n')
							pass
					else:
						LOG.write('Bad request. ' + PACK + '\n')
					c.close()
					GZIP.close()
				except socket.error:
					LOG.write('Connection error. ' + PACK + '\n')
					pass
			# reset buffered payload string..
			PAYLOAD=''
		# reset transport token..
		if time.strftime("%M") == '51': CALL=True
except Exception as e:
	print e.args[0]
	exit(2)
exit(0)

