FROM drupal:10.1-php8.2-apache

RUN apt update && apt -y upgrade && \
    apt -y install git 

