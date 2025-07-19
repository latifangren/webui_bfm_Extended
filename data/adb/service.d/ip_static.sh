#!/system/bin/sh
sleep 60
ip address add 192.168.43.1/24 dev wlan0
sleep 10
ip addr add 192.168.42.1/24 dev rndis0
