#!/usr/bin/python
#
# TODO:
#
# -get attachment
# -parse attachment
# -drop CSV
#

import poplib,email

#

try:# POP3
	s = poplib.POP3('[removed]',timeout=10)
	s.user('[removed]')
	s.pass_('[removed]')

	m = s.retr(s.stat()[0])#get last message
	msg = email.message_from_string('\n'.join(m[1]))# parse it..

	s.quit()
except:
	print "Failed to fetch mail."

print msg['Subject']
