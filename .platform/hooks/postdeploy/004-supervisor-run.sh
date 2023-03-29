#!/usr/bin/env bash
#if ps aux | grep -q "[/]usr/bin/supervisord"; then
#    echo "supervisor already running"
#    echo "Stop supervisor"
#    sudo -E systemctl stop supervisord
#fi
{
    echo "Trying to stop existing supervisord "
    sudo -E systemctl stop supervisord
 } ||{
     echo "No supervisord process found"
 }
echo "start supervisor"
sudo -E /usr/bin/python /usr/bin/supervisord -c /etc/supervisord.conf --pidfile /var/run/supervisord.pid

echo "Supervisor restart all programs"
sudo -E /usr/bin/supervisorctl restart all
