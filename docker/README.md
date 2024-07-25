OpenCart 4
============
Docker compose file for OpenCart 4.

## Docker Images
- OpenCart Image: [Bitnami OpenCart 4](https://hub.docker.com/r/bitnami/opencart/)
- MariaDB Image: [MariaDB 10.4](https://hub.docker.com/r/bitnami/mariadb)

## Pre-Requisites
- install composer `curl -sS https://getcomposer.org/installer | php`
- install docker-compose [http://docs.docker.com/compose/install/](http://docs.docker.com/compose/install/)

## Default Admin Account
- Admin page: [http://localhost:8080/administration/](http://localhost:8080/administration/)
- Username: admin
- Password: admin

## Usage
Build the plugin file
- ```composer run release```

Start the service:
- ```docker-compose up```

Destroy the container and start from scratch:
- ```docker-compose down -v```

## Plugin setup
You can follow the instruction using Extension Installer in the [Opencart 3.0.x KB Article](https://help.tawk.to/article/opencart-3x);

The plugin file can be found at `../tmp/tawkto.ocmod.zip`