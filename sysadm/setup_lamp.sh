#!/bin/bash

# LAMP
sudo yum install -y httpd24 php56 mysql55-server php56-mysqlnd php56-mbstring git

# phpMyAdmin
sudo yum install --enablerepo=epel -y phpmyadmin php-phpseclib*
