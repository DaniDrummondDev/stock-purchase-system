<?php

namespace App\Application\Commands;

final class ImportarCotahistCommand
{
    public function __construct(
        public readonly string $filePath,
    ) {}
}
