#!/bin/bash

TARGET="172.20.0.10"
LOG_FILE="/var/log/attacks/portscan.log"

echo "=== Port Scanning Attack ===" | tee -a $LOG_FILE
echo "Target: $TARGET" | tee -a $LOG_FILE
echo "Started: $(date)" | tee -a $LOG_FILE
echo "" | tee -a $LOG_FILE

echo "[*] Quick SYN scan..." | tee -a $LOG_FILE
nmap -sS -T4 $TARGET 2>&1 | tee -a $LOG_FILE
echo "" | tee -a $LOG_FILE

echo "[*] Comprehensive scan (all ports)..." | tee -a $LOG_FILE
nmap -sS -sV -p- -T4 $TARGET 2>&1 | tee -a $LOG_FILE
echo "" | tee -a $LOG_FILE

echo "[*] Aggressive scan with OS detection..." | tee -a $LOG_FILE
nmap -A -T5 $TARGET 2>&1 | tee -a $LOG_FILE
echo "" | tee -a $LOG_FILE

echo "=== Scan completed: $(date) ===" | tee -a $LOG_FILE
