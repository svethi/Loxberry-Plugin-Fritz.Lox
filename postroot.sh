#!/bin/sh

if [ -h "/etc/init.d/callmonitor" ]
then
	
	echo "<INFO> callmonitor stoppen."
	service callmonitor stop

	echo "<INFO> unlink /etc/init.d/callmonitor"
	rm -f "/etc/init.d/callmonitor"
fi