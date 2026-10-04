<?php

use App\Providers\AppServiceProvider;
use App\Providers\EventServiceProvider;
use App\Providers\WhatsAppEventServiceProvider;

return [
    AppServiceProvider::class,
    EventServiceProvider::class,
    WhatsAppEventServiceProvider::class,
];
