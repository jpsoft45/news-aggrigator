<?php

use App\Console\Commands\FetchNewsArticles;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Schedule::call('app:fetch-news-articles')->daily();
Artisan::command('app:fetch-news-articles', function () {
    $this->call('app:fetch-news-articles');})
->hourly();
Artisan::command('app:fetch-guardian-articles', function () {
    $this->call('app:fetch-guardian-articles');})
->hourly();
Artisan::command('app:fetch-n-y-times-articles', function () {
    $this->call('app:fetch-n-y-times-articles');})
->hourly();



