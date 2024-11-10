<?php

use App\Console\Commands\FetchNewsArticles;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('fetch-news-articles', function () {
    $this->call('fetch-news-articles');})
->hourly();



