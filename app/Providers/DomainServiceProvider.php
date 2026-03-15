<?php

namespace App\Providers;

use App\Domain\Client\Repositories\ClienteRepositoryInterface;
use App\Domain\Client\Repositories\ContaGraficaRepositoryInterface;
use App\Domain\Client\Repositories\CustodiaRepositoryInterface;
use App\Infrastructure\Persistence\Repositories\EloquentClienteRepository;
use App\Infrastructure\Persistence\Repositories\EloquentContaGraficaRepository;
use App\Infrastructure\Persistence\Repositories\EloquentCustodiaRepository;
use Illuminate\Support\ServiceProvider;

class DomainServiceProvider extends ServiceProvider
{
    public array $bindings = [
        ClienteRepositoryInterface::class => EloquentClienteRepository::class,
        ContaGraficaRepositoryInterface::class => EloquentContaGraficaRepository::class,
        CustodiaRepositoryInterface::class => EloquentCustodiaRepository::class,
    ];
}
