#!//bin/sh

print_usage() {
	echo "Usage : [direction (to/from)] [filename]"
	echo "This command is to send and recover files from vh40 server"
}

direction=$1
filename=$2
position1=""
position2=""

if [ -z $1 ] || [ -z $2 ]; then
	print_usage
	exit $err
fi

if [ "$direction" = "to" ]; then
	position1=$PWD/$filename
elif [ "$direction" = "from" ]; then
	position2="$filename ."
fi

set -x
scp -r "$position1" -oHostKeyAlgorithms=ssh-rsa -oForwardAgent=yes -oPubkeyAcceptedAlgorithms=+ssh-rsa $BNS_USER@bns-lamp-18.ecritel.net+SSH+$BNS_USER@ecrmut-wallix-02.ecritel.net:"$position2"
