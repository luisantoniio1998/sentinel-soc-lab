#!/usr/bin/env python3
"""
Tails /var/log/collect/webapp/all.log and forwards batches
to the Azure Monitor HTTP Data Collector API.
"""

import base64
import hashlib
import hmac
import json
import os
import time
from datetime import datetime, timezone

import requests

WORKSPACE_ID = os.environ["WORKSPACE_ID"]
WORKSPACE_KEY = os.environ["WORKSPACE_KEY"]
LOG_TYPE = "SecurityLab"
LOG_FILE = "/var/log/collect/webapp/all.log"
BATCH_SIZE = 10
FLUSH_INTERVAL = 30  # seconds


def build_signature(date, content_length):
    x_headers = f"x-ms-date:{date}"
    string_to_hash = f"POST\n{content_length}\napplication/json\n{x_headers}\n/api/logs"
    decoded_key = base64.b64decode(WORKSPACE_KEY)
    encoded_hash = base64.b64encode(
        hmac.new(decoded_key, string_to_hash.encode("utf-8"), digestmod=hashlib.sha256).digest()
    ).decode()
    return f"SharedKey {WORKSPACE_ID}:{encoded_hash}"


def post_to_azure(batch):
    body = json.dumps(batch).encode("utf-8")
    date = datetime.now(timezone.utc).strftime("%a, %d %b %Y %H:%M:%S GMT")
    signature = build_signature(date, len(body))
    url = f"https://{WORKSPACE_ID}.ods.opinsights.azure.com/api/logs?api-version=2016-04-01"
    headers = {
        "Content-Type": "application/json",
        "Authorization": signature,
        "Log-Type": LOG_TYPE,
        "x-ms-date": date,
    }
    try:
        r = requests.post(url, data=body, headers=headers, timeout=10)
        print(f"[azure] sent {len(batch)} events → HTTP {r.status_code}", flush=True)
    except Exception as e:
        print(f"[azure] error: {e}", flush=True)


def parse_event(line):
    entry = {
        "TimeGenerated": datetime.now(timezone.utc).isoformat(),
        "RawMessage": line,
        "Source": "security-lab",
        "EventType": "General",
    }
    if "LOGIN_ATTEMPT" in line or "LOGIN_SUCCESS" in line or "LOGIN_FAILED" in line:
        entry["EventType"] = "Authentication"
    elif "SEARCH_QUERY" in line:
        entry["EventType"] = "SearchQuery"
    elif "FILE_UPLOAD" in line:
        entry["EventType"] = "FileUpload"
    return entry


def tail(filepath):
    while not os.path.exists(filepath):
        print(f"[forwarder] waiting for {filepath}...", flush=True)
        time.sleep(5)
    with open(filepath, "r") as f:
        f.seek(0, 2)
        while True:
            line = f.readline()
            if line:
                yield line.strip()
            else:
                time.sleep(0.5)


print(f"[forwarder] started — watching {LOG_FILE}", flush=True)

batch = []
last_flush = time.time()

for line in tail(LOG_FILE):
    if not line:
        continue
    batch.append(parse_event(line))
    now = time.time()
    if len(batch) >= BATCH_SIZE or (now - last_flush) >= FLUSH_INTERVAL:
        post_to_azure(batch)
        batch = []
        last_flush = now
