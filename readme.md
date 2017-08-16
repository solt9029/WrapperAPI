# Purpose

- to be able to use web api quickly 

# Versions

- docker-compose: 1.11.1

- docker: 1.13.1

- composer: 1.3.2

# Setting Up the Envrionment

- copy config.php.example as config.php and fill your api keys in config.php

```
cd WrapperAPI
cp config.php.example config.php
```

- install libraries through composer

```
composer install
```

- set up the virtual environment using docker

```
cd docker.wrapperapi
docker-compose build
docker-compose up -d
```