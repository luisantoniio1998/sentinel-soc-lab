FROM kalilinux/kali-rolling

RUN apt-get update && apt-get install -y \
    sqlmap \
    nmap \
    masscan \
    hydra \
    nikto \
    dirb \
    gobuster \
    metasploit-framework \
    curl \
    wget \
    vim \
    net-tools \
    iputils-ping \
    python3 \
    python3-pip \
    && rm -rf /var/lib/apt/lists/*

RUN mkdir -p /root/scripts /var/log/attacks

WORKDIR /root/scripts

CMD ["/bin/bash"]
