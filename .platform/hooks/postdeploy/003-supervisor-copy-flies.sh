#!/usr/bin/env bash
sudo cp -f /var/app/current/.config/supervisor/laravel-worker.conf /etc/supervisor/conf.d/laravel-worker.conf
sudo cp -f /var/app/current/.config/supervisor/laravel-worker-ledger.conf /etc/supervisor/conf.d/laravel-worker-ledger.conf
sudo cp -f /var/app/current/.config/supervisor/supervisord.conf /etc/supervisord.conf

sudo chmod 755 /etc/supervisor/conf.d/laravel-worker.conf
sudo chown root:root /etc/supervisor/conf.d/laravel-worker.conf

sudo chmod 755 /etc/supervisor/conf.d/laravel-worker-ledger.conf
sudo chown root:root /etc/supervisor/conf.d/laravel-worker-ledger.conf

sudo chmod 755 /etc/supervisord.conf
sudo chown root:root /etc/supervisord.conf
