#!/usr/bin/python
#
# TODO:
#
#  CSV: Little-endian UTF-16 Unicode text
#  XLS: (xlrd)
# XLSX:
# 

import poplib,email,time,sys,re

runtime = time.strftime("%d.%m.%Y %H:%M")
logfile = '/var/log/logger.log'

#

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
			for part in range(1,len(msg.get_payload())):
				fn = email.Header.decode_header(msg.get_payload(part).get_filename())[0][0]
				if re.match('.*(csv)$',fn):
					print "Got CSV."
					#f = open('/root/log/file' + str(m) + str(part) + '.csv','w')
				#	f.write(msg.get_payload(part).get_payload(decode=True))
				#	f.close()
				#elif re.match('.*(xls)$',fn):
				#	print "Got XLS."
				#elif re.match('.*(xlsx)$',fn):
				#	print "Got XLSX."
	sess.quit()
except:
	log.write('Failed to fetch mail. ' + runtime + '\n')
	sys.exit(1)
log.close()

