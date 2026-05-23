#!/bin/bash
# Run this AFTER the container is running and Azure resources are created

echo "[*] Azure Monitor Agent Installation Script"

WORKSPACE_ID="YOUR_WORKSPACE_ID"
WORKSPACE_KEY="YOUR_WORKSPACE_KEY"

wget https://raw.githubusercontent.com/Microsoft/OMS-Agent-for-Linux/master/installer/scripts/onboard_agent.sh

sudo sh onboard_agent.sh \
  -w $WORKSPACE_ID \
  -s $WORKSPACE_KEY

echo "[*] AMA installation complete"
