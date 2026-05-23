FROM ubuntu:22.04

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y \
    rsyslog \
    curl \
    wget \
    vim \
    net-tools \
    iputils-ping \
    netcat-openbsd \
    python3 \
    python3-pip \
    && pip3 install requests --quiet \
    && rm -rf /var/lib/apt/lists/*

RUN mkdir -p /var/log/collect/webapp \
    && mkdir -p /var/log/collect/attacks \
    && mkdir -p /var/log/collect/system \
    && chown -R syslog:adm /var/log/collect \
    && chmod -R 755 /var/log/collect

COPY rsyslog.conf /etc/rsyslog.conf
COPY forwarder.py /usr/local/bin/forwarder.py
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh /usr/local/bin/forwarder.py

EXPOSE 514/udp 514/tcp

CMD ["/entrypoint.sh"]
