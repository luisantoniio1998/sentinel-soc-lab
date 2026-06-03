#!/bin/bash
# Remove stale PID file from previous run
rm -f /run/rsyslogd.pid
# Start rsyslog in background, then run the Azure forwarder in foreground
rsyslogd -n &
sleep 2
python3 /usr/local/bin/forwarder.py
