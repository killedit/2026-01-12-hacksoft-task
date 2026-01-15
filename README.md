# REST API for social media application

## Setup
```
git clone https://github.com/killedit/2026-01-12-hacksoft-task.git
cd 2026-01-12-hacksoft-task
docker compose up -d --build
```
If you want to follow the docker container logs remove the `-d` flad so you don't detach at the build process.

The application should run migrations and seeders.</br>

The back-end, Laravel Rest API, runs at:</br>
`http://127.0.0.1:8009`


## Overall project structure:
```
2026-01-12-hacksoft-task
    /docker
        /nginx
            default.conf
        /php
            /conf.d
                xdebug.ini
            entrypoint.sh
    /laravel                    # Laravel application.
        Dockerfile              # Backend container setup.
        .env                    # Laravel specific settings.
    docker-compose.yaml         # Where all containers are definied.
    .env                        # This is neede for MySQL docker container initialization.
```

## Connect to the database

Option 1: Connect to `hacksoft-mysql` container:

```
docker exec -it hacksoft-mysql-1 bash
mysql -u root -p
    admin123
use hacksoft;
show tables;
...
```

Option 2: Create a new db connection in DBeaver.

```
Server Host:    127.0.0.1
Port:           3309
Database:       hacksoft
Username:       laravel_user
Password:       user123

Driver properties:
    allowPublicKeyRetrieval     TRUE
    useSSL                      FALSE

Test Connection...
```

