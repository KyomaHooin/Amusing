#!/usr/bin/python
#
# Get serial data and send them gziped over HTTP..
#
# Data structure per sensor[AVR]:
#
#               'Got msg #311 : *ZF#T273H370L000B467' - 35 byte string + 2 byte CRLF[37 byte payload]
#               'Got msg #312 : *ZF#T273H370L000B467'
#                                .  .   .   .__ Serial number
#                                .  .   .______ Humidity
#                                .  .__________ Temperature
#                                ._____________ Local ID
#
# TODO: All processing in RAM..
#

import httplib
import serial
import socket
import time
import gzip
import os
import re

SENSOR_FILE='/root/amusing/rpi-sensor.txt'
PAYLOAD=37

try:

	try:# [IOError]
#		#LOG
		log = open('rpi-amusing.log','a',0)#non-buffering
		log.write('Program start: ' + time.strftime("%d.%m.%Y %H:%M") + '\n')
#		#SERIAL BUFFER
		sensors = {}
		with open(SENSOR_FILE,'r') as sfile:
			for line in sfile:
       				(key, value) = line.split(';')
      		 		sensors[key] = value
	except IOError:
		print 'Failed to open data stream.'
		exit(1)

	try:# [OSError]
		#DIR
		os.mkdir('archive')
		os.mkdir('http')
	except OSError:
		pass

#	MAIN

	while 1:

#		#SERIAL

		s = serial.Serial('/dev/ttyUSB0',9600)# 8,N,1 [default]
		data=s.read(2 * len(sensors) * PAYLOAD)# blocking.. bytes are doubled!
		s.close()
		log.write(data)
		log.write(time.strftime("%H:%M") + "\n")

#		#RE(CSV)

#		if re.match('^Got',string):
# 	        print re.sub('.* \*([A-Z]{2})#T(\d\d)(\d)H(\d\d)(\d).*$','\\1;temperature;\\2.\\3;humidity;\\4.\\5', string)

#		#GZIP[IOError]

#		f=location + '-' + time.strftime("%Y%m%dT%H%M%S") + '.csv.gz'
#       	gzip.open('http/' + f, 'wb').write(data)
#		gz=open('http/' + f,'rb')

#		#HTTP[socket.error]

#		c=httplib.HTTPConnection('amusing.nm.cz', '80', timeout=10)
#		c.request('POST', '[removed]', gz, header)
#		r=c.getresponse()
#		if (r.status == 200):
#			print "ok"
#		gz.close()
#		c.close()
except Exception as e:
	print e.args[0]
	exit(99)
#archiving..
#os.rename('http/' + f,'archive/' + f)
#log.write('Program end.\n')
