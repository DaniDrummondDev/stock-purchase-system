<?php

use App\Providers\AiAgentServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\DomainServiceProvider;

return [
    AppServiceProvider::class,
    DomainServiceProvider::class,
    AiAgentServiceProvider::class,
];
