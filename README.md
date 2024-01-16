# Intranet // Authors: OJT batch 2023 - 2024

requirements for windows:
- docker desktop 4.26.1
- wsl 2 ubuntu 22.04.3

Step 1: wsl
- $ git clone ssh://git@repo.ecomia.com:32022/jomer/intranet.git
- $ docker compose up -d --build

// if there some errors found during docker compose up, run this on wsl
$ sudo chmod 666 /var/run/docker.sock

Step 2: docker
# Install composer on docker drupal
// docker drupal terminal - bash 
- Composer install

Step 3: wsl
- chmod a+w sites/default/files
- chmod a+w sites/default/settings.php

