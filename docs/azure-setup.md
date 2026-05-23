# POC: Azure Sentinel Security Lab

A self-contained Docker-based security lab designed to generate realistic attack telemetry and forward it to **Azure Microsoft Sentinel** for SIEM detection, alerting, and incident response practice.

---

## Table of Contents

1. [Architecture](#architecture)
2. [Prerequisites](#prerequisites)
3. [Project Structure](#project-structure)
4. [Quick Start](#quick-start)
5. [Lab Components](#lab-components)
6. [Running Attacks](#running-attacks)
7. [Log Flow](#log-flow)
8. [Azure Sentinel Integration](#azure-sentinel-integration)
9. [KQL Detection Rules](#kql-detection-rules)
10. [Useful Commands](#useful-commands)
11. [Teardown](#teardown)

---

## Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                        macOS Host                           │
│                                                             │
│  ┌─────────────────────────────────────────────────────┐   │
│  │           Docker Network (172.20.0.0/24)            │   │
│  │                                                     │   │
│  │  ┌──────────────┐  ┌──────────────┐  ┌──────────┐  │   │
│  │  │   MySQL DB   │  │  Webapp(PHP) │  │  Kali    │  │   │
│  │  │ 172.20.0.5   │  │ 172.20.0.10  │  │ Attacker │  │   │
│  │  │ Port: 3306   │  │ Port: 8080   │  │172.20.0.20│  │   │
│  │  └──────────────┘  └──────┬───────┘  └────┬─────┘  │   │
│  │                           │               │         │   │
│  │                    ┌──────▼───────┐       │         │   │
│  │                    │   Syslog     │◄──────┘         │   │
│  │                    │  Collector   │                  │   │
│  │                    │ 172.20.0.30  │                  │   │
│  │                    │ Port: 514    │                  │   │
│  │                    └──────┬───────┘                  │   │
│  └───────────────────────────┼──────────────────────────┘   │
│                              │ Azure Monitor Agent           │
└──────────────────────────────┼──────────────────────────────┘
                               ▼
                  ┌────────────────────────┐
                  │    Azure Sentinel      │
                  │   Log Analytics WS     │
                  │   Detection Rules      │
                  │   Incidents & Alerts   │
                  └────────────────────────┘
```

| Container | Image | IP | Purpose |
|---|---|---|---|
| `mysql-db` | `mysql:8.0` | `172.20.0.5` | Vulnerable database backend |
| `webapp` | `php:8.1-apache` (custom) | `172.20.0.10` | Intentionally vulnerable PHP app — host port `8888` |
| `syslog-collector` | `ubuntu:22.04` (custom) | `172.20.0.30` | Rsyslog server + Azure Monitor Agent |
| `kali-attacker` | `kalilinux/kali-rolling` (custom) | `172.20.0.20` | Attack tooling (sqlmap, nmap, hydra…) |

---

## Prerequisites

- **macOS** with [Docker Desktop](https://www.docker.com/products/docker-desktop/) installed and running
- **Azure account** (free tier works — [Azure for Students](https://azure.microsoft.com/en-us/free/students/) or [GitHub Student Pack](https://education.github.com/pack))
- ~5 GB free disk space (Kali image is large)
- Ports `8080`, `514`, `3306` free on the host

```bash
# Verify Docker is running
docker --version
docker compose version
```

---

## Project Structure

```
security-lab/
├── docker-compose.yml              # Orchestration — 4 services, 1 network
├── webapp/
│   ├── Dockerfile                  # php:8.1-apache + mysqli
│   ├── init.sql                    # DB schema + seed data (users, products)
│   └── app/
│       ├── config.php              # DB + syslog config, log_to_syslog()
│       ├── index.php               # Landing page with attack hints
│       ├── login.php               # SQLi-vulnerable login
│       ├── dashboard.php           # Post-login page
│       ├── search.php              # SQLi-vulnerable product search
│       ├── upload.php              # RCE-vulnerable file upload
│       ├── info.php                # phpinfo() — info disclosure
│       └── logout.php
├── attacker/
│   ├── Dockerfile                  # Kali rolling + sqlmap, nmap, hydra, nikto…
│   └── scripts/
│       ├── sql-injection.sh        # Manual SQLi + automated sqlmap
│       ├── bruteforce.sh           # Hydra SSH brute force
│       ├── portscan.sh             # Nmap SYN / comprehensive / aggressive
│       └── web-scan.sh             # Nikto + gobuster directory enum
├── syslog-collector/
│   ├── Dockerfile                  # ubuntu:22.04 + rsyslog + netcat
│   ├── rsyslog.conf                # Routes logs by tag to /var/log/collect/*
│   └── install-ama.sh              # Azure Monitor Agent install script
├── configs/
│   └── azure/
│       └── dcr-config.json         # Data Collection Rule template
└── logs/                           # Bind-mounted — persists outside containers
    ├── webapp/                     # Apache + PHP logs
    ├── collected/                  # Rsyslog output (auth.log, search.log…)
    └── attacks/                    # Attack script output logs
```

---

## Quick Start

```bash
cd ~/Documents/Obsidian\ Vault/POC\ Sentinel/security-lab

# Build all images (first run: ~10-15 min — Kali is large)
docker compose build

# Start all services
docker compose up -d

# Check all containers are healthy
docker compose ps

# Open the vulnerable webapp
open http://localhost:8888
```

Expected output from `docker compose ps`:

```
NAME               STATUS
mysql-db           running (healthy)
syslog-collector   running (healthy)
webapp             running
kali-attacker      running
```

---

## Lab Components

### Vulnerable Web App — `http://localhost:8080`

| Page | Vulnerability | Attack Vector |
|---|---|---|
| `/login.php` | SQL Injection | Auth bypass, union-based, blind |
| `/search.php` | SQL Injection | Union-based, error-based |
| `/upload.php` | Unrestricted File Upload | Upload `.php` shell → RCE |
| `/info.php` | Information Disclosure | Full `phpinfo()` exposed |

**Test credentials:**

| User | Password | Role |
|---|---|---|
| `admin` | `admin123` | admin |
| `john` | `john123` | user |
| `alice` | `alice123` | user |
| `bob` | `password` | user |

### Kali Attacker Container

```bash
# Drop into attacker shell
docker exec -it kali-attacker /bin/bash

# All attack scripts are in /root/scripts
ls /root/scripts/
```

### Syslog Collector

Rsyslog listens on `514/udp` and `514/tcp`. Logs are routed by content:

| Log File | Contains |
|---|---|
| `/var/log/collect/webapp/auth.log` | `LOGIN_ATTEMPT`, `LOGIN_SUCCESS`, `LOGIN_FAILED` |
| `/var/log/collect/webapp/search.log` | `SEARCH_QUERY` events |
| `/var/log/collect/webapp/all.log` | All webapp events |
| `/var/log/collect/attacks/all.log` | Messages containing `attack`, `scan`, `exploit` |
| `/var/log/collect/system/all.log` | Everything else |

---

## Running Attacks

### From the Attacker Container

```bash
docker exec -it kali-attacker /bin/bash

cd /root/scripts

# SQL Injection (manual payloads + automated sqlmap)
./sql-injection.sh

# Port scanning (SYN, comprehensive, aggressive)
./portscan.sh

# Web vulnerability scan (Nikto + gobuster)
./web-scan.sh

# SSH brute force (Hydra)
./bruteforce.sh
```

### Manual SQLi Payloads (from browser)

```
# Auth bypass
admin' OR '1'='1'--

# Dump users via UNION
admin' UNION SELECT null,username,password,null,null FROM users--

# Time-based blind (5s delay on success)
admin' AND SLEEP(5)--

# Boolean blind
admin' AND '1'='1'--
```

### Watch Logs in Real Time

```bash
# Auth events (logins, SQLi attempts)
docker exec syslog-collector tail -f /var/log/collect/webapp/auth.log

# All webapp events
docker exec syslog-collector tail -f /var/log/collect/webapp/all.log

# Host-side (bind-mounted)
tail -f logs/collected/webapp/auth.log
```

---

## Log Flow

```
PHP app (login.php / search.php)
    │
    │  UDP syslog message → 172.20.0.30:514
    ▼
rsyslog (syslog-collector)
    │
    ├── /var/log/collect/webapp/auth.log    (auth events)
    ├── /var/log/collect/webapp/search.log  (search/SQLi)
    └── /var/log/collect/webapp/all.log     (everything)
    │
    │  Azure Monitor Agent (OMS)
    ▼
Log Analytics Workspace (Azure)
    │
    ▼
Microsoft Sentinel → Analytics Rules → Incidents
```

Docker container logs are also forwarded via the Docker `syslog` logging driver directly to the collector.

---

## Azure Sentinel Integration

### Step 1 — Create Azure Resources

```bash
# Login
az login

# Create resource group
az group create --name rg-sentinel-lab --location eastus

# Create Log Analytics Workspace
az monitor log-analytics workspace create \
  --resource-group rg-sentinel-lab \
  --workspace-name law-sentinel-lab \
  --location eastus

# Enable Sentinel on the workspace
az sentinel workspace create \
  --resource-group rg-sentinel-lab \
  --workspace-name law-sentinel-lab
```

### Step 2 — Get Workspace Credentials

```bash
# Workspace ID
az monitor log-analytics workspace show \
  --resource-group rg-sentinel-lab \
  --workspace-name law-sentinel-lab \
  --query customerId -o tsv

# Primary key
az monitor log-analytics workspace get-shared-keys \
  --resource-group rg-sentinel-lab \
  --workspace-name law-sentinel-lab \
  --query primarySharedKey -o tsv
```

### Step 3 — Install Azure Monitor Agent in Syslog Container

```bash
# Get a shell in the syslog container
docker exec -it syslog-collector /bin/bash

# Edit the script with your credentials first
vi /install-ama.sh
# Set WORKSPACE_ID and WORKSPACE_KEY

# Run the installer
bash /install-ama.sh
```

> The `install-ama.sh` script is at `syslog-collector/install-ama.sh`. Fill in `YOUR_WORKSPACE_ID` and `YOUR_WORKSPACE_KEY` before running.

### Step 4 — Create Data Collection Rule

Edit `configs/azure/dcr-config.json` and replace the placeholder values:

| Placeholder | Replace With |
|---|---|
| `YOUR_SUBSCRIPTION_ID` | Your Azure subscription ID |
| `YOUR_RG` | `rg-sentinel-lab` |
| `YOUR_WORKSPACE` | `law-sentinel-lab` |

```bash
# Deploy the DCR
az monitor data-collection rule create \
  --resource-group rg-sentinel-lab \
  --name dcr-seclab \
  --rule-file configs/azure/dcr-config.json
```

### Step 5 — Verify Data is Flowing

In Azure Portal → Log Analytics Workspace → Logs, run:

```kql
Syslog
| where TimeGenerated > ago(1h)
| order by TimeGenerated desc
| take 50
```

If rows appear, logs are flowing. Move on to creating detection rules.

---

## KQL Detection Rules

Paste these into **Sentinel → Analytics → New rule → Scheduled query**.

### 1. SQL Injection Attempt Detection

```kql
Syslog
| where TimeGenerated > ago(5m)
| where SyslogMessage contains "LOGIN_ATTEMPT"
| where SyslogMessage matches regex @"('|--|UNION|SELECT|OR\s+'1'='1|SLEEP\(|AND\s+')"
| extend
    IP = extract(@"IP=([^\s|]+)", 1, SyslogMessage),
    User = extract(@"User=([^\s|]+)", 1, SyslogMessage),
    Query = extract(@"Query=(.+?)(?:\s*\||\s*$)", 1, SyslogMessage)
| project TimeGenerated, IP, User, Query
| summarize AttackCount = count(), Queries = make_set(Query) by IP, User, bin(TimeGenerated, 1m)
| where AttackCount >= 1
```

### 2. Brute Force Login Detection

```kql
Syslog
| where TimeGenerated > ago(10m)
| where SyslogMessage contains "LOGIN_FAILED"
| extend
    IP = extract(@"IP=([^\s|]+)", 1, SyslogMessage),
    User = extract(@"User=([^\s|]+)", 1, SyslogMessage)
| summarize FailedAttempts = count(), Users = make_set(User) by IP, bin(TimeGenerated, 5m)
| where FailedAttempts >= 5
```

### 3. Successful Login After Multiple Failures (Credential Compromise)

```kql
let failures = Syslog
| where TimeGenerated > ago(30m)
| where SyslogMessage contains "LOGIN_FAILED"
| extend IP = extract(@"IP=([^\s|]+)", 1, SyslogMessage)
| summarize FailCount = count() by IP;
Syslog
| where TimeGenerated > ago(30m)
| where SyslogMessage contains "LOGIN_SUCCESS"
| extend IP = extract(@"IP=([^\s|]+)", 1, SyslogMessage)
| join kind=inner failures on IP
| where FailCount >= 3
| project TimeGenerated, IP, FailCount, SyslogMessage
```

### 4. Port Scan Detection

```kql
Syslog
| where TimeGenerated > ago(5m)
| where SyslogMessage contains "scan" or SyslogMessage contains "nmap"
| extend IP = extract(@"IP=([^\s|]+)", 1, SyslogMessage)
| summarize ScanEvents = count() by IP, bin(TimeGenerated, 1m)
| where ScanEvents >= 3
```

### 5. Suspicious Search Query (SQLi in Search)

```kql
Syslog
| where TimeGenerated > ago(5m)
| where SyslogMessage contains "SEARCH_QUERY"
| where SyslogMessage matches regex @"(UNION|SELECT|'|--|;|xp_)"
| extend
    IP = extract(@"IP=([^\s|]+)", 1, SyslogMessage),
    Query = extract(@"Query=(.+)", 1, SyslogMessage)
| project TimeGenerated, IP, Query
```

---

## Useful Commands

```bash
# Start lab
docker compose up -d

# Stop lab (keep data)
docker compose stop

# View all logs
docker compose logs -f

# View specific service logs
docker compose logs -f webapp

# Rebuild and restart one service
docker compose build webapp && docker compose up -d webapp

# Shell into any container
docker exec -it webapp /bin/bash
docker exec -it kali-attacker /bin/bash
docker exec -it syslog-collector /bin/bash
docker exec -it mysql-db mysql -u webapp -pwebapp123 webapp_db

# Check collected logs
docker exec syslog-collector ls -la /var/log/collect/webapp/
docker exec syslog-collector cat /var/log/collect/webapp/auth.log

# Check MySQL login attempts
docker exec mysql-db mysql -u webapp -pwebapp123 webapp_db \
  -e "SELECT * FROM login_attempts ORDER BY timestamp DESC LIMIT 20;"
```

---

## Teardown

```bash
# Stop and remove containers (keep volumes/images)
docker compose down

# Full clean — removes containers, volumes, and built images
docker compose down -v --rmi local

# Remove log files
rm -rf logs/collected/* logs/webapp/* logs/attacks/*
```

---

## Notes

- All credentials in this lab are intentionally weak and publicly documented — this is by design.
- **Never expose this lab to the public internet.** All services are bound to `localhost` or the internal Docker network.
- The Kali image pulls metasploit-framework which is ~3 GB — first build takes time.
- The syslog logging driver on `webapp` requires `syslog-collector` to be healthy before startup — the `healthcheck` in `docker-compose.yml` handles this ordering.
- Azure Monitor Agent (OMS) inside a container requires `privileged: true` — this is set on the syslog service.
