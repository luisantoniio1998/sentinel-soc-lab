# Sentinel SOC Lab

![Docker](https://img.shields.io/badge/Docker-2496ED?style=for-the-badge&logo=docker&logoColor=white)
![Azure](https://img.shields.io/badge/Microsoft_Sentinel-0089D6?style=for-the-badge&logo=microsoft-azure&logoColor=white)
![Kali Linux](https://img.shields.io/badge/Kali_Linux-557C94?style=for-the-badge&logo=kali-linux&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-00000F?style=for-the-badge&logo=mysql&logoColor=white)
![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg?style=for-the-badge)

A fully containerised **offensive/defensive security lab** that generates realistic attack telemetry (SQL injection, brute force, port scanning, RCE) and forwards it to **Microsoft Sentinel** for SIEM detection, KQL analytics, and incident response practice — all running on a single Mac with Docker.

---

## Overview

This lab simulates a real-world SOC pipeline end-to-end:

```
Kali Attacker ──► Vulnerable PHP App ──► Syslog Collector ──► Microsoft Sentinel
  (offensive)         (target)            (log pipeline)        (SIEM/detection)
```

Everything runs locally in Docker. No VMs, no cloud compute. Only Sentinel lives in Azure (free tier eligible).

---

## Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                          macOS Host                             │
│                                                                 │
│  ┌───────────────────────────────────────────────────────────┐  │
│  │              Docker Network  172.20.0.0/24                │  │
│  │                                                           │  │
│  │  ┌─────────────┐   ┌─────────────┐   ┌───────────────┐  │  │
│  │  │  MySQL DB   │   │  Webapp     │   │ Kali Attacker │  │  │
│  │  │ 172.20.0.5  │   │ 172.20.0.10 │   │ 172.20.0.20   │  │  │
│  │  │ Port: 3306  │◄──│ Port: 8888  │◄──│ sqlmap/nmap/  │  │  │
│  │  └─────────────┘   └──────┬──────┘   │ hydra/nikto   │  │  │
│  │                           │          └───────────────┘  │  │
│  │                    syslog │ UDP 514                      │  │
│  │                    ┌──────▼──────────┐                   │  │
│  │                    │ Syslog Collector│                   │  │
│  │                    │  172.20.0.30   │                   │  │
│  │                    │  rsyslog +     │                   │  │
│  │                    │  Python fwdr   │                   │  │
│  │                    └──────┬─────────┘                   │  │
│  └───────────────────────────┼───────────────────────────────┘  │
│                              │ HTTPS / HTTP Data Collector API  │
└──────────────────────────────┼──────────────────────────────────┘
                               ▼
                  ┌────────────────────────┐
                  │   Microsoft Sentinel   │
                  │  Log Analytics WS      │
                  │  KQL Analytics Rules   │
                  │  Incidents & Alerts    │
                  └────────────────────────┘
```

| Container | Image | IP | Ports | Role |
|---|---|---|---|---|
| `mysql-db` | `mysql:8.0` | `172.20.0.5` | `3306` | Vulnerable database |
| `webapp` | `php:8.1-apache` | `172.20.0.10` | `8888→80` | Intentionally vulnerable PHP app |
| `syslog-collector` | `ubuntu:22.04` | `172.20.0.30` | `514/udp+tcp` | Rsyslog + Azure forwarder |
| `kali-attacker` | `kalilinux/kali-rolling` | `172.20.0.20` | — | Attack tooling |

---

## Attack Scenarios

| Attack | Tool | Target | Log Generated |
|---|---|---|---|
| SQL Injection (auth bypass) | Manual / sqlmap | `/login.php` | `LOGIN_ATTEMPT` with raw query |
| SQL Injection (union-based) | Manual / sqlmap | `/search.php` | `SEARCH_QUERY` with payload |
| Brute Force | Hydra | SSH / login | `LOGIN_FAILED` × N |
| Port Scanning | Nmap | webapp | Network events |
| Web Vulnerability Scan | Nikto / Gobuster | webapp | `SEARCH_QUERY` / access logs |
| RCE via File Upload | Manual | `/upload.php` | `FILE_UPLOAD` |

---

## Prerequisites

- **macOS** with [Docker Desktop](https://www.docker.com/products/docker-desktop/) installed and running
- **Azure account** — free tier works ([Azure for Students](https://azure.microsoft.com/en-us/free/students/) gives $100 credit)
- ~5 GB free disk space
- Ports `8888`, `514`, `3306` free on the host

---

## Quick Start

```bash
# 1. Clone the repo
git clone https://github.com/luisantoniio1998/sentinel-soc-lab.git
cd sentinel-soc-lab

# 2. Add your Azure credentials
cp .env.example .env
# Edit .env with your Workspace ID and Primary Key

# 3. Build and run
docker compose build
docker compose up -d

# 4. Verify
docker compose ps
open http://localhost:8888
```

> First build takes ~10–15 min (Kali image is large). Subsequent starts are instant.

---

## Azure Sentinel Setup

### 1 — Create Resources in Azure Portal

| Step | Where | What to create |
|---|---|---|
| 1 | [portal.azure.com](https://portal.azure.com) | Resource Group: `rg-sentinel-lab` |
| 2 | Log Analytics Workspaces | Workspace: `law-sentinel-lab` (region: Spain Central or nearest allowed) |
| 3 | Microsoft Sentinel | Add `law-sentinel-lab` as the workspace |

### 2 — Get Your Credentials

```bash
# Install Azure CLI (Mac)
brew install azure-cli

az login

az monitor log-analytics workspace get-shared-keys \
  --resource-group rg-sentinel-lab \
  --workspace-name law-sentinel-lab \
  --query primarySharedKey -o tsv
```

The Workspace ID is on the workspace **Overview** page.

### 3 — Configure .env

```bash
cp .env.example .env
```

Edit `.env`:
```env
WORKSPACE_ID=xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
WORKSPACE_KEY=your+primary+key+here==
```

### 4 — Rebuild and Run

```bash
docker compose build syslog
docker compose up -d
```

Logs will appear in Sentinel under the `SecurityLab_CL` custom table within 3–5 minutes.

---

## Running Attacks

### From the Attacker Container

```bash
docker exec -it kali-attacker /bin/bash
cd /root/scripts

./sql-injection.sh   # SQLi payloads + automated sqlmap
./portscan.sh        # Nmap SYN, comprehensive, aggressive
./web-scan.sh        # Nikto + gobuster directory enum
./bruteforce.sh      # Hydra SSH brute force
```

### Manual SQLi Payloads (browser at `http://localhost:8888`)

```sql
-- Auth bypass
admin' OR '1'='1'--

-- Dump all users
admin' UNION SELECT null,username,password,null,null FROM users--

-- Time-based blind (5s delay)
admin' AND SLEEP(5)--
```

### Watch Logs in Real Time

```bash
# Structured auth events
docker exec syslog-collector tail -f /var/log/collect/webapp/auth.log

# Azure forwarder activity
docker logs -f syslog-collector
```

---

## KQL Detection Rules

Run these in **Microsoft Sentinel → Logs** against the `SecurityLab_CL` table.

### SQL Injection Detection

```kql
SecurityLab_CL
| where TimeGenerated > ago(5m)
| where EventType_s == "Authentication"
| where RawMessage_s matches regex @"('|--|UNION|SELECT|OR\s+'1'='1|SLEEP\()"
| extend
    IP = extract(@"IP=([^\s|]+)", 1, RawMessage_s),
    User = extract(@"User=([^\s|]+)", 1, RawMessage_s),
    Query = extract(@"Query=(.+?)(\s*\||\s*$)", 1, RawMessage_s)
| project TimeGenerated, IP, User, Query
| order by TimeGenerated desc
```

### Brute Force Detection

```kql
SecurityLab_CL
| where TimeGenerated > ago(10m)
| where RawMessage_s contains "LOGIN_FAILED"
| extend IP = extract(@"IP=([^\s|]+)", 1, RawMessage_s)
| summarize FailedAttempts = count() by IP, bin(TimeGenerated, 5m)
| where FailedAttempts >= 5
```

### Successful Login After Multiple Failures

```kql
let failures =
    SecurityLab_CL
    | where TimeGenerated > ago(30m)
    | where RawMessage_s contains "LOGIN_FAILED"
    | extend IP = extract(@"IP=([^\s|]+)", 1, RawMessage_s)
    | summarize FailCount = count() by IP;
SecurityLab_CL
| where TimeGenerated > ago(30m)
| where RawMessage_s contains "LOGIN_SUCCESS"
| extend IP = extract(@"IP=([^\s|]+)", 1, RawMessage_s)
| join kind=inner failures on IP
| where FailCount >= 3
| project TimeGenerated, IP, FailCount, RawMessage_s
```

### SQLi in Search (Union Attack)

```kql
SecurityLab_CL
| where TimeGenerated > ago(5m)
| where EventType_s == "SearchQuery"
| where RawMessage_s matches regex @"(UNION|SELECT|'|--|xp_)"
| extend
    IP = extract(@"IP=([^\s|]+)", 1, RawMessage_s),
    Query = extract(@"Query=(.+)", 1, RawMessage_s)
| project TimeGenerated, IP, Query
```

---

## Project Structure

```
sentinel-soc-lab/
├── README.md
├── docker-compose.yml
├── .env.example                        ← copy to .env and fill in credentials
├── .gitignore
├── webapp/
│   ├── Dockerfile                      ← php:8.1-apache + mysqli + sockets
│   ├── init.sql                        ← schema + vulnerable seed data
│   └── app/
│       ├── config.php                  ← DB config + log_to_syslog()
│       ├── login.php                   ← SQLi-vulnerable login
│       ├── search.php                  ← SQLi-vulnerable search
│       ├── upload.php                  ← RCE-vulnerable file upload
│       ├── dashboard.php
│       ├── index.php
│       ├── info.php                    ← phpinfo() — info disclosure
│       └── logout.php
├── attacker/
│   ├── Dockerfile                      ← Kali rolling + sqlmap/nmap/hydra/nikto
│   └── scripts/
│       ├── sql-injection.sh
│       ├── bruteforce.sh
│       ├── portscan.sh
│       └── web-scan.sh
├── syslog-collector/
│   ├── Dockerfile                      ← ubuntu:22.04 + rsyslog + python3
│   ├── rsyslog.conf                    ← routes logs by tag/content
│   ├── forwarder.py                    ← tails logs, POSTs to Azure HTTP API
│   └── entrypoint.sh                  ← starts rsyslog + forwarder
├── configs/
│   └── azure/
│       └── dcr-config.json            ← Data Collection Rule template
└── docs/
    └── azure-setup.md                 ← detailed Azure configuration guide
```

---

## Log Flow

```
PHP app  ──UDP 514──►  rsyslog  ──►  /var/log/collect/webapp/auth.log
                                  ──►  /var/log/collect/webapp/all.log
                                  ──►  /var/log/collect/attacks/all.log
                          │
                    forwarder.py
                          │
                    HTTP POST (HTTPS)
                          │
                    Azure HTTP Data Collector API
                          │
                    SecurityLab_CL  ──►  Microsoft Sentinel
```

---

## Useful Commands

```bash
# Start / stop
docker compose up -d
docker compose down

# Shell into any container
docker exec -it kali-attacker /bin/bash
docker exec -it webapp /bin/bash
docker exec -it syslog-collector /bin/bash

# Live log tail
docker exec syslog-collector tail -f /var/log/collect/webapp/auth.log
docker logs -f syslog-collector

# Check MySQL login attempts
docker exec mysql-db mysql -u webapp -pwebapp123 webapp_db \
  -e "SELECT username, ip_address, success, timestamp FROM login_attempts ORDER BY timestamp DESC LIMIT 20;"

# Full clean reset
docker compose down -v --rmi local
```

---

## Troubleshooting

| Problem | Fix |
|---|---|
| Webapp doesn't start | Check `syslog-collector` is healthy first: `docker compose ps` |
| No logs in Sentinel | Check forwarder: `docker logs syslog-collector` — should show `HTTP 200` |
| Port conflict on 8888 | Change `"8888:80"` in `docker-compose.yml` to any free port |
| Kali build is slow | ~3 GB image — only on first build. Use `docker compose build --no-cache` to force rebuild |
| `.env` not found | Copy `.env.example` → `.env` and fill in your Workspace ID and Key |

---

## Disclaimer

> This lab contains **intentionally vulnerable software** for educational and research purposes only.
> Do **not** expose any service to the public internet.
> All credentials are hardcoded by design — never reuse them outside this lab.

---

## License

MIT — see [LICENSE](LICENSE) for details.
