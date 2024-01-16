# Intranet // Authors: OJT batch 2023 - 2024

requirements for windows:
- docker desktop 4.26.1 or latest
- wsl 2 ubuntu 22.04.3 or latest

Git global setup / wsl
- git config --global user.name "Your Name"     // git config --global user.name "Jomer Luague"
- git config --global user.email "Your Email"   // git config --global user.email "jomerluague.sos@gmail.com"

Step 1: wsl
- $ git clone ssh://git@repo.ecomia.com:32022/jomer/intranet.git
- $ docker compose up -d --build

- $ sudo chmod 666 /var/run/docker.sock
// if there some errors found during build, run this on wsl

Step 2: docker
// Install composer on docker drupal
// docker drupal terminal - bash 
- Composer install

Step 3: wsl
- chmod a+w sites/default/files
- chmod a+w sites/default/settings.php

