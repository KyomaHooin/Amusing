#!/usr/bin/python
#
# TODO:
#
#  CSV: Little-endian UTF-16 Unicode text -> Prumstav DS100
#  XLS: (xlrd)
# XLSX:
# 

import poplib,email,time,sys,re

runtime = time.strftime("%d.%m.%Y %H:%M")
logfile = '/var/log/logger.log'

prumstav = {'185':'prumstav1',
	    '170':'prumstav2',
	    '180':'prumstav3',
	    '194':'prumstav4',
	    '165':'prumstav5'}

pracom = {'Data1':'pracom1',
	  'Data2':'pracom2',
	  'Data3':'pracom3',
	  'Data4':'pracom4',
	  'Data5':'pracom5',
	  'Data6':'pracom6',
	  'Data7':'pracom7',
	  'Data8':'pracom8'}

# FUNC


def csv_parse(buff,name):
	csv = open('/root/data/pracom-' + time.strftime("%Y%m%dT%H%M$S") + '.csv.tmp','a')
	for line in buff.decode('utf-16').encode('utf-8').splitlines()[1:]:
		l = line.split(',')
		t = time.strftime("%Y%m%dT%H%M%S",time.strptime(l[1],"%d.%m.%Y %H:%M:%S"))
		if len(l) == 5:
			csv.write(str(name) + ';temperature;' + l[2] + '.' + l[3] + ';' + t + '\n')
			csv.write(str(name) + ';humidity;' + l[4] + ';' + t + '\n')
		if len(l) == 4:
			csv.write(str(name) + ';temperature;' + l[2] + ';' + t + '\n')
			csv.write(str(name) + ';humidity;' + l[3] + ';' + t + '\n')
	csv.close()
	#os.rename('/var/www/sensors/data/ + tmp,'/var/www/sensors/data/' + csv)
# MAIN

try:# MAIN
        log = open(logfile,'a')
except:
        print('Failed to open log file.')
        sys.exit(4)
try:# POP3
	sess = poplib.POP3('[removed]',timeout=10)
	sess.user('[removed]')
	sess.pass_('[removed]')
	msgs = sess.stat()[0]# get last message
	#if msgs == 0:
	#	log.write('Nothing to parse. ' + runtime + '\n')
	#else:
	for m in range(1,msgs + 1):
		popmsg = sess.retr(m)
		msg = email.message_from_string('\n'.join(popmsg[1]))# email parser
		if msg.is_multipart():
			for part in range(1,len(msg.get_payload())):# only attachments
				fn = email.Header.decode_header(msg.get_payload(part).get_filename())[0][0]# filename
				if re.match('.*(csv)$',fn):
					print "Got CSV.", fn
					csv_parse(msg.get_payload(part).get_payload(decode=True),part)
					#csv_parse(msg.get_payload(part).get_payload(decode=True),fn)
				#elif re.match('.*(xls)$',fn):
				#	print "Got XLS.", fn
				#elif re.match('.*(xlsx)$',fn):
				#	print "Got XLSX.", fn
	sess.quit()
except Exception as e:
	print e
	log.write('Failed to fetch mail. ' + runtime + '\n')
	sys.exit(1)
log.close()

