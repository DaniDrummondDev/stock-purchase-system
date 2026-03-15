<?php

namespace App\Presentation\Http\Controllers\Api;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Stock Purchase System API',
    description: 'API para o Sistema de Compra Programada de Ações',
    contact: new OA\Contact(name: 'API Support', email: 'support@stockpurchase.local')
)]
#[OA\Server(url: 'http://localhost:8000', description: 'Local Development')]
abstract class Controller
{
}
