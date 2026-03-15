<?php

namespace App\Infrastructure\B3;

class CotahistFileReader
{
    /**
     * @return \Generator<string>
     */
    public function readLines(string $filePath): \Generator
    {
        if (! file_exists($filePath)) {
            throw new \InvalidArgumentException("Arquivo não encontrado: {$filePath}");
        }

        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            throw new \RuntimeException("Não foi possível abrir o arquivo: {$filePath}");
        }

        try {
            while (($line = fgets($handle)) !== false) {
                $line = rtrim($line, "\r\n");

                // Convert ISO-8859-1 to UTF-8 if needed
                if (! mb_check_encoding($line, 'UTF-8')) {
                    $line = mb_convert_encoding($line, 'UTF-8', 'ISO-8859-1');
                }

                yield $line;
            }
        } finally {
            fclose($handle);
        }
    }
}
