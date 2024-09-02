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
- Admin page: [http://127.0.0.1.nip.io:8080/administration/](http://127.0.0.1.nip.io:8080/administration/)
- Username: admin
- Password: admin

## Hostname
This docker compose utilizes nip.io to route `127.0.0.1.nip.io` to localhost for ease of setting up test suite
while preserving the ability to access the website via web browser. This option can be changed using the
`WEB_HOST` variable.

## Usage
Build the plugin file
- ```./build.sh```

Start the service:
- ```docker-compose up --build```

Destroy the container and start from scratch:
- ```docker-compose down -v```

## Multistore setup
In the following steps, replace `<path>` with the your multistore path, e.g. `/second_store`

1. Enter the console of opencart container: `docker exec -u 0 -it <container_id> bash`

2. Add an alias for apache
- Edit this file `/opt/bitnami/apache/conf/httpd.conf`
- Add this line `Alias <path> /opt/bitnami/opencart/`

3. Restart apache server `apachectl -k graceful`

4. Add new store on Opencart: System > Settings > Add New(Store)

5. For `Store URL` enter: `http://127.0.0.1.nip.io:8080<path>/`. Take note the "/" at the end

6. After saving, the new store should be accessible at the URL above.

## Plugin setup
You can follow the instruction using Extension Installer in the [Opencart 3.0.x KB Article](https://help.tawk.to/article/opencart-3x);

The plugin file can be found at `../tmp/tawkto.ocmod.zip`

## Testing

### Local Test Configuration

These are the environment variables needed to run the selenium tests locally using composer script

| Environment Variable | Description | Required |
|---|---|---|
| TAWK_PROPERTY_ID | Property Id | Yes |
| TAWK_WIDGET_ID | Widget Id | Yes |
| TAWK_USERNAME | tawk.to account username | Yes |
| TAWK_PASSWORD | tawk.to account password | Yes |
| WEB_HOST | Wordpress web hostname | Yes |
| WEB_PORT | Wordpress web port | No |
| SECOND_STORE | Second store route | Yes |
| SELENIUM_BROWSER | Browser type (chrome, firefox, edge) | Yes |
| SELENIUM_HOST | Selenium host | Yes |
| SELENIUM_PORT | Selenium port | No |
| SELENIUM_HEADLESS | Headless mode | No |

To simplify testing, you can place your environment configuration in a `.env.local` file.

Example contents:
```
export TAWK_PROPERTY_ID='<TAWK_PROPERTY_ID>'
export TAWK_WIDGET_ID='<TAWK_WIDGET_ID>'
export TAWK_USERNAME='<TAWK_USERNAME>'
export TAWK_PASSWORD='<TAWK_PASSWORD>'
export WEB_HOST='127.0.0.1.nip.io'
export WEB_PORT='8080'
export SECOND_STORE='second_store'
export SELENIUM_BROWSER='chrome'
export SELENIUM_HOST='localhost'
export SELENIUM_PORT='4444'
export SELENIUM_HEADLESS='true'
```

And simply run

`source .env.local && composer run test`