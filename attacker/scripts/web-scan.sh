#!/bin/bash

TARGET="http://172.20.0.10"
LOG_FILE="/var/log/attacks/webscan.log"

echo "=== Web Vulnerability Scan ===" | tee -a $LOG_FILE
echo "Target: $TARGET" | tee -a $LOG_FILE
echo "Started: $(date)" | tee -a $LOG_FILE
echo "" | tee -a $LOG_FILE

echo "[*] Running Nikto scan..." | tee -a $LOG_FILE
nikto -h $TARGET 2>&1 | tee -a $LOG_FILE
echo "" | tee -a $LOG_FILE

echo "[*] Directory enumeration with gobuster..." | tee -a $LOG_FILE
gobuster dir -u $TARGET \
  -w /usr/share/wordlists/dirb/common.txt \
  -t 50 \
  2>&1 | tee -a $LOG_FILE
echo "" | tee -a $LOG_FILE

echo "=== Scan completed: $(date) ===" | tee -a $LOG_FILE
