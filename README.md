# REST API for social media application

## Setup
```
git clone https://github.com/killedit/2026-01-12-hacksoft-task.git
cd 2026-01-12-hacksoft-task
docker compose up -d --build
```
If you want to follow the docker container logs remove the `-d` flag so you don't detach at the build process.

The application should run migrations and seeders.</br>

The back-end Laravel Rest API should runs at:</br>
`http://127.0.0.1:8009`

## Overall project structure:

```
2026-01-12-hacksoft-task
    /docker
        /nginx
            default.conf            # Nginx. Reverse proxy.
        /php
            /conf.d
                xdebug.ini          # Xdebug. Test coverage.
            entrypoint-laravel.sh   # Bash script that runs migrations and seeders in the Laravel container.
            entrypoint-worker.sh    # The scheduler and the queue only need to wait only for working MySQL and Redis.
    /laravel                        # Back-end application.
        Dockerfile                  # Backend container setup.
        .env                        # Laravel specific settings.
    /{fronend}                      # Future {frontend}.
    docker-compose.yaml             # Where all containers are definied.
    .env                            # This is neede for MySQL docker container initialization.
```

## Connect to the database

Option 1: Connect to `hacksoft-mysql-1` container:

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

## REST API resources

I have created a `Test` user that should play the role of admin with password `test123` in db seeder.

| Method | Endpoint | Controller | Description |
| --- | --- | --- | --- |
| `POST` | `/api/login` | AuthController@login | Login. |
| `POST` | `/api/logout` | AuthController@logout | Logout. |
| `GET` | `/api/me` | AuthController@me | List current user. Useful for testing. |

### How to test the API endpoints

1. Curl.

```
curl -X POST http://localhost:8009/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"test123"}'

curl -X POST http://localhost:8009/api/logout \
  -H "Authorization: Bearer 2|n8647i0Fc4o8GSiCphPRuSTuyqlqfhVjvZBolvUGce02f90f" \
  -H "Content-Type: application/json"

curl --location 'http://127.0.0.1:8009/api/me' \
--header 'Content-Type: application/json' \
--header 'Authorization: Bearer 6|tIPBGCSUJZSRJZLv33oIFmouJKuWCEkSTAGKaBN87d29ffb3'

curl -X POST http://localhost:8009/api/register \
  -F "name=User" \
  -F "email=user@example.com" \
  -F "password=user123" \
  -F "short_description=Just a new user." \
  -F "profile_picture=@/path/to/image.jpg"
```

You can run tests with example profile pictures `resources/images/`. The model will save them in `storage/public/profile-pictures/`. The current logged-in resource `http://127.0.0.1:8009/api/me` will get the value from the databse `profile-pictures/cGsZbrk5pRoGe33p33sbsl6mrNLFGnZYvwhSq9fT.jpg`. Just add the app url infront to display the image in the browser `http://127.0.0.1:8009/storage/profile-pictures/cGsZbrk5pRoGe33p33sbsl6mrNLFGnZYvwhSq9fT.jpg`.

2. Postman.

There is a Postman collection and environment that need to be imported in.

`/laravel/postman/2026-01-12-hacksoft-task.postman_environment.json` </br>
`/laravel/postman/2026-01-12-hacksoft-task.postman_collection.json`

![Postman collection](laravel/resources/images/2026-01-12-hacksoft-task-postman-collection-login-resource-token.png) 
![Postman environment](laravel/resources/images/2026-01-12-hacksoft-task-postman-environment-variable-token.png) 

3. OpenAPI Swagger.


Tasks:
- Sandboxed users should not be able to log in!
- Profile resource.
- Posts resource.
- Feed resource.
- Sheduler.
- Queue.
- Admin panel !!!
- Rate limiting. Trottling.
- Sanctum middleware for CORS.
- Avoid n+1 query problem ::with();.
- use SoftDeletes;.
- Proper datetime conversion with Carbon middleware.
- Migrations. Seeders.
- Caching.
- Postman collections and environment.
- OpenAPI Swagger.
- Integration tests.
- Test coverage.
- README.md. Printscreens.
- Build process test !!!
- Remove comments //HERE.
- Contributor.
- Email.

Done:
- Docker initial setup.
- Authentication resource.
- Registration resource. Images are stored in 
- Handle 405 method not allowed as 404 to prevent information leakeage. http://127.0.0.1:8009/api/{login} will return json response instead of debug backtrace.