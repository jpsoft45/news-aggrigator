# News Aggregator

## Steps to run the code

### 1 . Clone the repository
### 2 . cd into the project directory
### 3 . cp .env.example .env i have share here my env that i had used during the development
```APP_NAME=Laravel
APP_ENV=local
APP_KEY=base64:aA6ZtyOaoAGqEv2S+cKEJkP4A4ZMb3oQGXqlJSrN32U=
APP_DEBUG=true
APP_TIMEZONE=UTC
APP_URL=http://localhost

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file
# APP_MAINTENANCE_STORE=database

PHP_CLI_SERVER_WORKERS=4

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=database
CACHE_PREFIX=

MEMCACHED_HOST=127.0.0.1

REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=c8c0d5e2ea7d81
MAIL_PASSWORD=2c8ae920d929e4
# MAIL_MAILER=smtp
# MAIL_HOST=smtp.mailtrap.io
# MAIL_PORT=2525
# MAIL_USERNAME=2525
# MAIL_PASSWORD=null
# MAIL_ENCRYPTION=null
# MAIL_FROM_ADDRESS="hello@example.com"
# MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

VITE_APP_NAME="${APP_NAME}"

SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://meilisearch:7700

MEILISEARCH_NO_ANALYTICS=false
NYTIMES_API_KEY=P1z4enAtC0KouO0OiqRgDBPqLU3sd87w
GUARDIAN_API_KEY=a605ee43-4361-4d44-84e2-483bf522de83
NEWS_API_KEY=a1552c2570d04473974c3fd23872c591
```
### 4. composer install
### 5. Start docker desktop
### 6. ./vendor/bin/sail up -d
    - It will auto fetch all the dependencies and start the containers.
### 7. ./vendor/bin/sail artisan migrate
## For the fetch articles from the news source api run the following command it will store the article in local database.
    - ./vendor/bin/sail artisan app:fetch-news-articles
    - ./vendor/bin/sail artisan app:fetch-guardian-articles
    - ./vendor/bin/sail artisan app:fetch-n-y-times-articles
## For the swagger
    - Use url http://0.0.0.0/api/documentation

## For the run test case
    - ./vendor/bin/sail composer test
    - For preview code coverage `coverage/index.html` run this file from the root folder

## API url
    - http://0.0.0.0/api/v1/

