## Install
npm i
npm run build
composer update
php artisan migrate
php artisan migrate:fresh --seed

## Optimize
php artisan optimize:clear

## Run
npm run dev
php artisan serve --host=0.0.0.0 --port=8050
php artisan reverb:start --debug
php artisan queue:work

## Account
Email:- admin@admin.com
Pass:- 12345678
Roll:- admin
Guard:- web

Email:- developer@developer.com
Pass:- 12345678
Roll:- developer
Guard:- web

Email:- client@client.com
Pass:- 12345678
Roll:- client
Guard:- web

Email:- retailer@retailer.com
Pass:- 12345678
Roll:- retailer
Guard:- web

Email:- trainer@trainer.com
Pass:- 12345678
Roll:- admin
Guard:- api

Email:- user@user.com
Pass:- 12345678
Roll:- user
Guard:- api
