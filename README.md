# Intranet
## Author: OJT batch 2023 - 2024
**Michael Tisbe | Jomer Luague | Rustum Goden**

requirements for windows:
- docker desktop 4.26.1 or latest
- wsl 2 ubuntu 22.04.3 or latest
- SQLyog

### Git global setup / wsl

- git config --global user.name "Your Name"  [git config --global user.name "Jomer Luague"]
- git config --global user.email "Your Email"  [git config --global user.email "jomerluague.sos@gmail.com"]

Step 1: wsl
- $ git clone ssh://git@repo.ecomia.com:32022/jomer/intranet.git
- cd intranet
- $ docker compose up -d --build
<!-- if there some errors found during build, run this on wsl -->
- $ sudo chmod 666 /var/run/docker.sock

Step 2: docker drupal
<!-- Install composer on docker drupal -->
<!-- docker drupal terminal - bash -->
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

# Project Name

[Project Description]

## About the Project

[Provide a brief description of the project and its purpose.]

## Getting Started

[Explain how to get started with the project. Include details about prerequisites and installation steps.]

## Usage

[Provide information on how to use the project. Include examples and usage scenarios.]

### Configuration

[If applicable, include details on how users can configure or customize the project.]

## Note

> This is an important note related to the project. Add any additional details or special instructions you want users to be aware of.

## Contributing

[Explain how others can contribute to your project. Include guidelines for submitting bug reports, feature requests, or code contributions.]

## License

[Specify the license under which the project is distributed. Include a link to the full license file.]

## Contact

[Provide contact information for the project maintainer or team. This can include email addresses, GitHub profiles, or other means of communication. Encourage users to reach out with questions or feedback.]

