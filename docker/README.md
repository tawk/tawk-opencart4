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

## Multistore setup
In the following steps, replace `<path>` with the your multistore path, e.g. `/second_store`

1. Enter the console for `opencart-40` container: `docker exec -u 0 -it <container_id> bash`

2. Add an alias for apache
- Edit this file `/opt/bitnami/apache/conf/httpd.conf`
- Add this line `Alias <path> /opt/bitnami/opencart/`

3. Restart apache server `apachectl -k graceful`

4. Add new store on Opencart: System > Settings > Add New(Store)

5. For `Store URL` enter: `http://localhost:8080<path>/`. Take note the "/" at the end

6. After saving, the new store should be accessible at the URL above.

## Plugin setup
You can follow the instruction using Extension Installer in the [Opencart 3.0.x KB Article](https://help.tawk.to/article/opencart-3x);

The plugin file can be found at `../tmp/tawkto.ocmod.zip`