#!/usr/bin/python
#
# HTTP POST GZIP transport
#

import httplib
import socket
import gzip
import time
import os

location='pocernice'
header={'Content-Type':'application/octet-stream','X-Location':'pocernice-27062016T174405'}
data="""sensor1;1;data1;123456
sensor2;1;data2;123456
sensor3;1;data3;123456
"""

try:
	log = open('rpi.log','a')
	log.write('Program start: ' + time.strftime("%d.%m.%Y %H:%M") + '\n')
except IOError:
	print 'Read only FS exiting!\n'
	exit(1)
try:
	os.mkdir('archive')
	os.mkdir('http')
except OSError:
	pass
try:
	f=location + '-' + time.strftime("%Y%m%dT%H%M%S") + '.csv.gz'
	gzip.open('http/' + f, 'wb').write(data)
except IOError:
	log.write('Failed to gzip CSV data.\n')
	exit(2)
try:
	gz=open('http/' + f,'rb')
except IOError:
	log.write('Failed to open CSV archive.\n')
	exit(3)
try:
	c=httplib.HTTPConnection('amusing.nm.cz', '80', timeout=10)
	c.request('POST', '[removed]', gz, header)
	r=c.getresponse()
	if (r.status == 200):
		print "ok"
	gz.close()
	c.close()
except socket.error:
	log.write('HTTP connection error.\n')

os.rename('http/' + f,'archive/' + f)		
log.write('Program end.\n')

exit(0)
