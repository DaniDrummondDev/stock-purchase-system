<?php

namespace App\Providers;

use App\Domain\Basket\Repositories\CestaRepositoryInterface;
use App\Domain\Client\Repositories\ClienteRepositoryInterface;
use App\Domain\Client\Repositories\ContaGraficaRepositoryInterface;
use App\Domain\Client\Repositories\CustodiaRepositoryInterface;
use App\Domain\MarketData\Repositories\CotacaoRepositoryInterface;
use App\Infrastructure\Persistence\Repositories\EloquentCestaRepository;
use App\Infrastructure\Persistence\Repositories\EloquentClienteRepository;
use App\Infrastructure\Persistence\Repositories\EloquentContaGraficaRepository;
use App\Infrastructure\Persistence\Repositories\EloquentCotacaoRepository;
use App\Infrastructure\Persistence\Repositories\EloquentCustodiaRepository;
use Illuminate\Support\ServiceProvider;

class DomainServiceProvider extends ServiceProvider
{
    public array $bindings = [
        ClienteRepositoryInterface::class => EloquentClienteRepository::class,
        ContaGraficaRepositoryInterface::class => EloquentContaGraficaRepository::class,
        CustodiaRepositoryInterface::class => EloquentCustodiaRepository::class,
        CestaRepositoryInterface::class => EloquentCestaRepository::class,
        CotacaoRepositoryInterface::class => EloquentCotacaoRepository::class,
    ];
}
