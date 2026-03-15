<?php

namespace App\Domain\Client\Entities;

class ContaGrafica
{
    private string $id;

    private string $clienteId;

    private string $numero;

    public function __construct(string $id, string $clienteId, string $numero)
    {
        $this->id = $id;
        $this->clienteId = $clienteId;
        $this->numero = $numero;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function clienteId(): string
    {
        return $this->clienteId;
    }

    public function numero(): string
    {
        return $this->numero;
    }

    public static function gerarNumero(): string
    {
        return 'CG-'.strtoupper(substr(md5(uniqid()), 0, 8));
    }
}
