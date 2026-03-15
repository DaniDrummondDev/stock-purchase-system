<?php

namespace App\Domain\MarketData\Services;

use App\Domain\MarketData\Entities\Cotacao;

class CotahistParserService
{
    private const RECORD_TYPE_DETAIL = '01';

    private const VALID_BDI_CODES = ['02', '96'];

    private const VALID_MARKET_TYPES = ['010', '020'];

    private const LINE_LENGTH = 245;

    /**
     * @param  string[]  $lines
     * @return Cotacao[]
     */
    public function parseLines(array $lines): array
    {
        $cotacoes = [];

        foreach ($lines as $line) {
            $cotacao = $this->parseLine($line);

            if ($cotacao !== null) {
                $cotacoes[] = $cotacao;
            }
        }

        return $cotacoes;
    }

    public function parseLine(string $line): ?Cotacao
    {
        if (strlen($line) < self::LINE_LENGTH) {
            return null;
        }

        $recordType = $this->extractField($line, 0, 2);

        if ($recordType !== self::RECORD_TYPE_DETAIL) {
            return null;
        }

        $codBdi = $this->extractField($line, 10, 2);

        if (! in_array($codBdi, self::VALID_BDI_CODES, true)) {
            return null;
        }

        $tpMerc = $this->extractField($line, 24, 3);

        if (! in_array($tpMerc, self::VALID_MARKET_TYPES, true)) {
            return null;
        }

        return new Cotacao(
            ticker: trim($this->extractField($line, 12, 12)),
            dataPregao: $this->parseDate($this->extractField($line, 2, 8)),
            precoFechamento: $this->parsePrice($this->extractField($line, 108, 13)),
            precoAbertura: $this->parsePrice($this->extractField($line, 56, 13)),
            precoMaximo: $this->parsePrice($this->extractField($line, 69, 13)),
            precoMinimo: $this->parsePrice($this->extractField($line, 82, 13)),
            tipoMercado: $this->mapTipoMercado($tpMerc),
            codBdi: $codBdi,
            volume: $this->parseVolume($this->extractField($line, 170, 18)),
        );
    }

    private function extractField(string $line, int $start, int $length): string
    {
        return substr($line, $start, $length);
    }

    private function parsePrice(string $raw): float
    {
        return (float) ltrim($raw, '0') / 100;
    }

    private function parseVolume(string $raw): float
    {
        return (float) ltrim($raw, '0') / 100;
    }

    private function parseDate(string $raw): \DateTimeImmutable
    {
        $date = \DateTimeImmutable::createFromFormat('Ymd', $raw);

        if ($date === false) {
            throw new \InvalidArgumentException("Data inválida no COTAHIST: {$raw}");
        }

        return $date->setTime(0, 0);
    }

    private function mapTipoMercado(string $tpMerc): string
    {
        return match ($tpMerc) {
            '010' => 'padrao',
            '020' => 'fracionario',
            default => throw new \InvalidArgumentException("Tipo de mercado desconhecido: {$tpMerc}"),
        };
    }
}
