# Sentinel SOC Lab

I use this lab to generate attack traffic against a vulnerable app and follow its logs into Microsoft Sentinel.

The Docker setup runs a PHP app, MySQL, a Kali container, and a log collector. I use the resulting events to practise KQL queries.

## Quick start

```bash
git clone https://github.com/luisantoniio1998/sentinel-soc-lab.git
cd sentinel-soc-lab

cp .env.example .env
# Add your Azure Workspace ID and Key

docker compose build
docker compose up -d
```

Then open:

```text
http://localhost:8888
```

## Architecture

```text
Kali attacker -> vulnerable PHP app -> syslog collector -> Microsoft Sentinel
```

The main containers are:

- `webapp` — intentionally vulnerable PHP app
- `mysql-db` — database used by the app
- `syslog-collector` — collects logs and forwards them
- `kali-attacker` — runs attack tools and scripts

## Running attacks

```bash
docker exec -it kali-attacker /bin/bash
cd /root/scripts

./sql-injection.sh
./bruteforce.sh
./portscan.sh
./web-scan.sh
```

Or run several at once:

```bash
docker exec kali-attacker bash -c "cd /root/scripts && chmod +x *.sh && ./sql-injection.sh" &
docker exec kali-attacker bash -c "cd /root/scripts && ./bruteforce.sh" &
docker exec kali-attacker bash -c "cd /root/scripts && ./portscan.sh" &
docker exec kali-attacker bash -c "cd /root/scripts && ./web-scan.sh" &
```

## Azure setup

1. Create a Log Analytics workspace
2. Enable Microsoft Sentinel
3. Get the workspace ID and primary key
4. Add them to `.env`

Example:

```env
WORKSPACE_ID=xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
WORKSPACE_KEY=your+primary+key+here==
```

## Basic KQL checks

Query everything:

```kql
SecurityLab_CL
| order by TimeGenerated desc
```

Check failed logins:

```kql
SecurityLab_CL
| where RawMessage_s contains "LOGIN_FAILED"
| project TimeGenerated, RawMessage_s
| order by TimeGenerated desc
```

Check for SQL injection-like activity:

```kql
SecurityLab_CL
| where EventType_s == "Authentication"
| where RawMessage_s matches regex @"('|--|UNION|SELECT|OR\s+'1'='1|SLEEP\()"
| project TimeGenerated, RawMessage_s
| order by TimeGenerated desc
```

## Useful commands

```bash
# Start / stop
docker compose up -d
docker compose down

# Check containers
docker compose ps

# Logs
docker logs -f syslog-collector

# Shell into containers
docker exec -it webapp /bin/bash
docker exec -it kali-attacker /bin/bash
docker exec -it syslog-collector /bin/bash
```

## Troubleshooting

- If nothing appears in Sentinel, check `docker logs -f syslog-collector`
- If port `8888` is in use, change it in `docker-compose.yml`
- If `.env` is missing, copy `.env.example` and fill it in
- If the web app is not starting, check `docker compose ps`

## Lab scope

This lab is intentionally vulnerable and meant for learning only.

Do not expose it to the public internet.

## License

MIT
