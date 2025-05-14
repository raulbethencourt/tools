#!/bin/bash

USERNAME=$1
PASSWORD=$2

if [ -f ~/.ssh/$USERNAME ]; then
	echo "SSH key already exists"
else
	ssh-keygen -t rsa -b 4096 -C "$USERNAME" -f ~/.ssh/$USERNAME -N ""
fi

PUBKEY=$(cat ~/.ssh/$USERNAME.pub)

SERVER_LIST="/home/rabeta/dev/tools/scripts/add_bns_user/servers.txt"

while read -r OS CONECTION RAUL_PASSWORD; do
	case "$OS" in
	"CentOS")
		ssh -n raul-bns@$CONECTION "echo \"$RAUL_PASSWORD\" | sudo bash -c '
            if id -u $USERNAME >/dev/null 2>&1; then echo \"User already exists\"; else useradd $USERNAME && echo $PASSWORD | passwd $USERNAME --stdin; fi && \
                if getent group apache >/dev/null; then usermod -a -G apache $USERNAME; fi && \
                if [ -d \"/home/$USERNAME/.ssh\" ]; then echo \".ssh folder alreday exist\"; else mkdir -p /home/$USERNAME/.ssh; fi && \
                echo $PUBKEY >> /home/$USERNAME/.ssh/authorized_keys && \
                chown -R $USERNAME:apache /home/$USERNAME/.ssh && \
                chmod 700 /home/$USERNAME/.ssh && \
                chmod 600 /home/$USERNAME/.ssh/authorized_keys'"
		;;
	"Debian")
		ssh -n -oHostKeyAlgorithms=ssh-rsa -oForwardAgent=yes -oPubkeyAcceptedAlgorithms=+ssh-rsa -l $CONECTION ecrmut-wallix-02.ecritel.net -t "sudo bash -c '
            if id -u $USERNAME >/dev/null 2>&1; then echo \"User already exists\"; else adduser --disabled-password --gecos \"\" $USERNAME && echo $USERNAME:$PASSWORD | chpasswd; fi && \
                usermod -g adm $USERNAME && \
                usermod -aG www-data $USERNAME && \
                if [ -d \"/home/$USERNAME/.ssh\" ]; then echo \".ssh folder alreday exist\"; else mkdir -p /home/$USERNAME/.ssh; fi && \
                echo $PUBKEY >> /home/$USERNAME/.ssh/authorized_keys && \
                chown -R $USERNAME:adm /home/$USERNAME/.ssh && \
                chmod 700 /home/$USERNAME/.ssh && \
                chmod 600 /home/$USERNAME/.ssh/authorized_keys'"
		;;
	*)
		echo "Unsupported OS: $OS"
		;;
	esac

done <$SERVER_LIST
