#!/usr/bin/python
#
# TODO:
#
# -get attachment
# -parse attachment
# -drop CSV
#

import poplib,email,time,sys

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

	if msgs == 0:
		log.write('Nothing to parse. ' + runtime + '\n')
	else:
		for m in range(1,msgs + 1):
			popmsg = sess.retr(msgs)
			msg = email.message_from_string('\n'.join(popmsg[1]))# email parser
			print msg['Subject']
	sess.quit()
except:
	log.write('Failed to fetch mail. ' + runtime + '\n')
	sys.exit(1)
log.close()
