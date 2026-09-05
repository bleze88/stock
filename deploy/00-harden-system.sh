#!/bin/bash
# Durcissement système de base pour le VPS Debian 13 (votre-domaine.example)
# A executer avec sudo sur le VPS. Idempotent : peut etre relance sans casse.
set -euo pipefail

echo "== Mise a jour du systeme =="
export DEBIAN_FRONTEND=noninteractive
apt-get update -y
apt-get upgrade -y

echo "== Installation des paquets de durcissement =="
apt-get install -y ufw fail2ban unattended-upgrades apt-listchanges chrony

echo "== Pare-feu (ufw) =="
ufw --force reset
ufw default deny incoming
ufw default allow outgoing
ufw allow 22/tcp comment 'SSH'
ufw allow 80/tcp comment 'HTTP (redirect only)'
ufw allow 443/tcp comment 'HTTPS'
ufw --force enable
ufw status verbose

echo "== SSH hardening =="
SSHD_CONF=/etc/ssh/sshd_config
cp -n "$SSHD_CONF" "${SSHD_CONF}.bak.$(date +%s)" || true

set_sshd_option() {
  local key="$1" value="$2"
  if grep -qE "^\s*#?\s*${key}\b" "$SSHD_CONF"; then
    sed -i -E "s|^\s*#?\s*${key}\b.*|${key} ${value}|" "$SSHD_CONF"
  else
    echo "${key} ${value}" >> "$SSHD_CONF"
  fi
}

set_sshd_option PasswordAuthentication no
set_sshd_option PermitRootLogin no
set_sshd_option PubkeyAuthentication yes
set_sshd_option ChallengeResponseAuthentication no
set_sshd_option KbdInteractiveAuthentication no
set_sshd_option X11Forwarding no
set_sshd_option AllowTcpForwarding no
set_sshd_option MaxAuthTries 3
set_sshd_option ClientAliveInterval 300
set_sshd_option ClientAliveCountMax 2

sshd -t
systemctl reload ssh

echo "== Fail2ban (jail SSH) =="
cat > /etc/fail2ban/jail.local <<'EOF'
[sshd]
enabled = true
port = 22
filter = sshd
maxretry = 5
findtime = 10m
bantime = 1h
backend = systemd
EOF
systemctl enable --now fail2ban
systemctl restart fail2ban

echo "== Mises a jour automatiques de securite =="
cat > /etc/apt/apt.conf.d/20auto-upgrades <<'EOF'
APT::Periodic::Update-Package-Lists "1";
APT::Periodic::Unattended-Upgrade "1";
EOF
cat > /etc/apt/apt.conf.d/50unattended-upgrades <<'EOF'
Unattended-Upgrade::Origins-Pattern {
        "origin=Debian,codename=${distro_codename},label=Debian-Security";
        "origin=Debian,codename=${distro_codename}-security,label=Debian-Security";
};
Unattended-Upgrade::Remove-Unused-Dependencies "true";
Unattended-Upgrade::Automatic-Reboot "false";
EOF
systemctl enable --now unattended-upgrades

echo "== NTP (chrony) =="
systemctl enable --now chrony

echo "== Durcissement systeme termine =="
