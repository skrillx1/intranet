# Intranet
## Author: OJT batch 2023 - 2024
**Michael Tisbe | Jomer Luague | Rustum Goden**

requirements for windows: install

- docker desktop 4.26.1 or latest
- wsl 2 ubuntu 22.04.3 or latest
- SQLyog

### Git global setup / wsl
> this during first setup for Git init
- git config --global user.name "Your Name"  
> note: git config --global user.name "Jomer Luague"
- git config --global user.email "Your Email"  
> note: git config --global user.email "jomerluague.sos@gmail.com"

Step 1: wsl
- $ git clone ssh://git@repo.ecomia.com:32022/jomer/intranet.git
- cd intranet
- $ docker compose up -d --build
> if there some errors found during build, run this on wsl
- $ sudo chmod 666 /var/run/docker.sock

Step 2: docker drupal
> Install composer on docker drupal
> docker drupal terminal - bash
- Composer install

Step 3: wsl
- cd intranet/drupal
- chmod a+w sites/default/files
- chmod a+w sites/default/settings.php
- chmod go-w sites/default/settings.php

Step 4: SQLyog

**Configuration**

**Host address:** localhost

**username:** root

**password:** LfS59aJ69bGungkr

**port:** 10001

- import Database.sql
