# REST API for social media application

![Hacksoft task home](laravel/resources/images/2026-01-12-hacksoft-task-home.png)

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

Relationship Diagram of the tables:
```
Users
- hasMany (Posts) - we need # of posts by one user (task requirements)
- hasMany (Likes) - we need # of likes on posts created by that one user (task requirements)

Posts
- belongsTo (User) - userFK
- belongsToMany (Users) - likes via pivot table

Likes
- belongsTo (User) - userFK
- belongsTo (Post) - postFK
```

## REST API resources

I have created a `Test` user that should play the role of admin with password `test123` in db seeder.

| Method | Endpoint | Controller | Description |
| --- | --- | --- | --- |
| `POST` | `/api/login` | AuthController@login | Login with an approved user. |
| `POST` | `/api/logout` | AuthController@logout | Logout currently loggedin user. |
| `POST` | `/api/register` | AuthController@register | Register a unapproved user. |
| `GET` | `/api/me` | ProfileController@show | List current user's full data. |
| `POST` | `/api/me` | ProfileController@update | Change loggedin user's name, description and profile_picture. PATCH will not work here. |
| `GET` | `/api/posts{?cursor=prev_next_cursor_value}` | PostController@index | List all posts with `likers_count`, `likers` and `author`. User cursor in the response for pagination. |
| `POST` | `/api/posts` | PostController@store | Create a post. |
| `POST` | `/api/posts/{post_id}/like` | PostController@toggleLike | Toggle like/dislike on a post. |
| `DELETE` | `/api/posts/{post_id}/delete` | PostController@destroy | Delete a post. User can delete only posts they have created. |


### How to test the API endpoints

1. Curl.
- {} dynamic values

```bash
curl -X POST http://127.0.0.1:8009/api/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"test@example.com","password":"test123"}'

curl -X POST http://127.0.0.1:8009/api/logout \
  -H "Authorization: Bearer 2|n8647i0Fc4o8GSiCphPRuSTuyqlqfhVjvZBolvUGce02f90f" \
  -H "Content-Type: application/json"

curl -X POST http://127.0.0.1:8009/api/register \
  -H 'Accept: application/json'
  -H 'Content-Type: application/json' \
  -F 'name="User"' \
  -F 'email="user@example.com"' \
  -F 'password="user123"' \
  -F 'description="Just a new user."' \
  -F 'profile_picture=@"/absolute/path/to/photo.jpg"'

curl -X GET http://127.0.0.1:8009/api/me \
  -H 'Content-Type: application/json' \
  -H 'Authorization: Bearer 6|tIPBGCSUJZSRJZLv33oIFmouJKuWCEkSTAGKaBN87d29ffb3'

# This "hack" below is because of the file upload. $_FILES parsing only works for POST. PATCH + JSON will make text-only uploads and the requirements ask to be able to change everything without email and password.

curl -X POST  http://127.0.0.1:8009/api/me \
  -H 'Accept: application/json'
  -H 'Content-Type: application/json' \
  -H 'Authorization: Bearer 6|tIPBGCSUJZSRJZLv33oIFmouJKuWCEkSTAGKaBN87d29ffb3'
  -F '_method="PATCH"' \
  -F 'name="User has changed the name."' \
  -F 'description="User has changed the description."' \
  -F 'profile_picture=@"/absolute/path/to/photo.jpg"'

curl -X GET 'http://127.0.0.1:8009/api/posts' \
-H 'Accept: application/json' \
-H 'Authorization: Bearer 1|SVskYbt2jcYPpp5OwdVFzcqnhROyFbnHh7tUZJCn76fd14a8'

# NB! Added pagination will show results in DESC manner, meaning the newest first. At bottom of the json response is `next_cursor` which you can use like this `GET /api/posts?cursor={next_or_previous_cursor_value}`. Also for easy testing in PostController@index change `->cursorPaginate(20);` to smt small like 2.

http://127.0.0.1:8009/api/posts?cursor={eyJjcmVhdGVkX2F0IjoiMjAyNi0wMS0yMSAyMDoxNjoxNCIsIl9wb2ludHNUb05leHRJdGVtcyI6dHJ1ZX0}

curl -X POST 'http://127.0.0.1:8009/api/posts' \
-H 'Accept: application/json' \
-H 'Content-Type: application/json' \
-H 'Authorization: Bearer 2|tyaT0Ym6BDrlZf7vGo68PRgJmVj1JTODlTYWZBl989168284' \
-d'{
    "content":"This is a new post content."
}'

curl -X POST 'http://127.0.0.1:8009/api/posts/{1}/like' \
-H 'Accept: application/json' \
-H 'Authorization: Bearer 1|SVskYbt2jcYPpp5OwdVFzcqnhROyFbnHh7tUZJCn76fd14a8'

curl -X DELETE 'http://127.0.0.1:8009/api/posts/{3}/delete' \
-H 'Accept: application/json' \
-H 'Authorization: Bearer 1|SVskYbt2jcYPpp5OwdVFzcqnhROyFbnHh7tUZJCn76fd14a8'
```

You can run tests with example profile pictures `resources/images/`. The model will save them in `storage/public/profile-pictures/`. The current logged-in resource `http://127.0.0.1:8009/api/me` will get the value from the databse `profile-pictures/cGsZbrk5pRoGe33p33sbsl6mrNLFGnZYvwhSq9fT.jpg`. Just add the app url infront to display the image in the browser `http://127.0.0.1:8009/storage/profile-pictures/cGsZbrk5pRoGe33p33sbsl6mrNLFGnZYvwhSq9fT.jpg`.

2. Postman.

![Postman preview](laravel/resources/images/2026-01-12-hacksoft-task-postman-preview.png)

There is a Postman collection and Postman environment that need to be imported in.

`/laravel/postman/2026-01-12-hacksoft-task.postman_environment.json` </br>
`/laravel/postman/2026-01-12-hacksoft-task.postman_collection.json`

![Postman collection](laravel/resources/images/2026-01-12-hacksoft-task-postman-collection-login-resource-token.png)
![Postman environment](laravel/resources/images/2026-01-12-hacksoft-task-postman-environment-variable-token.png)

3. OpenAPI Swagger.

After logging-in the token is automatically passed so you can test the whole documentation. All endpoints should work as intended and are not only previews.

`http://127.0.0.1:8009/api/documentation`

![Swagger](laravel/resources/images/2026-01-12-hacksoft-task-swagger.png)


### Admin panel

The admin panel is installed with Filament. Filament is FOSS and no paid features. I've set an admin with email:`test@example.com` and password:`test123` in the db seeder.</br>
You can only approve newly registered and unapproved `Users`. The counter will show their number.</br>
Photos and descriptions are stored via the `register` resource.</br>

[http://localhost:8009/admin]

![Postman RegisterUser](laravel/resources/images/2026-01-12-hacksoft-task-postman-register-user.png)
![Postman Filament Users List](laravel/resources/images/2026-01-12-hacksoft-task-filament-users-list.png)
![Postman Filament Edit User](laravel/resources/images/2026-01-12-hacksoft-task-filament-edit-user.png)

User that is not approved will not be able to login.

![Postman Postman Unapproved](laravel/resources/images/2026-01-12-hacksoft-task-postman-unapproved-user-login.png)

Soft deleting user results in cascade soft deleting of their posts. Only admin user can delete other users.
A logged in user can make CRUD operations only on their posts. They cannot assign/change author of a post.

![Filament Soft Delete User Cascade Delete Posts](laravel/resources/images/2026-01-12-hacksoft-task-soft-delete-user-cascade-posts.png)

### Scheduler

A cronjob, but done in Laravel. It will run every day at 0h 0mins. Next values (*) are day, month, weekday. At first the cron should show no results are deleted. We can simulate deletion with a simple query to set a deletion date older than 10 days and running the cron.

```sql
update `posts` set `deleted_at` = '2026-01-11 14:13:02' where `id` = {id};
```

```bash
tihomir@ubuntu:~/2026-01-12-hacksoft-task$ docker exec -it hacksoft-laravel-1 bash
root@69da3775225c:/var/www/html# php artisan schedule:list

  0 0 * * *  php artisan purge:old-posts ............................ Next Due: 9 hours from now

root@69da3775225c:/var/www/html# php artisan app:purge-old-posts
Purged 0 old posts.
root@69da3775225c:/var/www/html# php artisan app:purge-old-posts
Purged 1 old posts.
```

### Worker

By moving the deletion logic in a Job and Dispatcher we ensure that if we had to delete millions of records it will be executed in the background in the queue.

```bash
root@69da3775225c:/var/www/html# php artisan queue:listen

	INFO  Processing jobs from the [default] queue.

root@69da3775225c:/var/www/html# php artisan queue:work

	INFO  Processing jobs from the [default] queue.

	2026-01-22 14:41:33 App\Jobs\PurgePostJob .............. RUNNING
	2026-01-22 14:41:33 App\Jobs\PurgePostJob ......... 33.99ms DONE
	2026-01-22 14:41:33 App\Jobs\PurgePostJob .............. RUNNING
	2026-01-22 14:41:33 App\Jobs\PurgePostJob ......... 24.88ms DONE
```

For this to happen we need in antoher terminal to run the command below. It will send the two records I have updated to the queue.

```bash
root@69da3775225c:/var/www/html# php artisan app:purge-old-posts
	Dispatched 2 posts to the queue for purging.
```


Tasks:
- README.md. Printscreens.
- Integration tests.
- Test coverage.
- Build process test !!!
- Contributor.
- Email.

Done:
- Docker initial setup.
- Authentication resource.
- Registration resource. Store images.
- Sanctum middleware for CORS.
- Handle 405 method not allowed as 404 to prevent information leakeage. http://127.0.0.1:8009/api/{login} will return json response instead of debug backtrace.
- Sandboxed users should not be able to log in!
- Admin panel !!!
- Profile resource.
- Posts resource.
- Feed resource.
- use SoftDeletes;.
- Avoid n+1 query problem ::with();.
- Migrations. Seeders.
- Postman collections and environment.
- Sheduler.
- Queue.
- Postman collections and environment.
- OpenAPI Swagger.
<!-- - Caching. -->
<!-- - Proper datetime conversion with Carbon middleware. -->
<!-- - Rate limiting. Trottling. -->