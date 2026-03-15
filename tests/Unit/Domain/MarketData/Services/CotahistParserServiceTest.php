<?php

use App\Domain\MarketData\Services\CotahistParserService;

function sampleLine(string $ticker = 'PETR4       ', string $bdi = '02', string $tpmerc = '010', string $date = '20260225'): string
{
    // Build a 245-char COTAHIST detail line with exact field positions
    $line = str_repeat(' ', 245);

    // Place fields at exact positions (0-indexed)
    $line = substr_replace($line, '01', 0, 2);                        // 0-1: record type
    $line = substr_replace($line, $date, 2, 8);                       // 2-9: date YYYYMMDD
    $line = substr_replace($line, $bdi, 10, 2);                       // 10-11: BDI code
    $line = substr_replace($line, str_pad($ticker, 12), 12, 12);      // 12-23: ticker
    $line = substr_replace($line, $tpmerc, 24, 3);                    // 24-26: market type
    $line = substr_replace($line, '0000000003520', 56, 13);           // 56-68: preco abertura (35.20)
    $line = substr_replace($line, '0000000003650', 69, 13);           // 69-81: preco maximo (36.50)
    $line = substr_replace($line, '0000000003480', 82, 13);           // 82-94: preco minimo (34.80)
    $line = substr_replace($line, '0000000003580', 108, 13);          // 108-120: preco fechamento (35.80)
    $line = substr_replace($line, '000000000010740000', 170, 18);     // 170-187: volume (107400.00)

    return $line;
}

test('parse line returns cotacao for valid detail line', function () {
    $parser = new CotahistParserService;
    $cotacao = $parser->parseLine(sampleLine());

    expect($cotacao)->not->toBeNull();
    expect($cotacao->ticker())->toBe('PETR4');
    expect($cotacao->dataPregao()->format('Y-m-d'))->toBe('2026-02-25');
    expect($cotacao->precoFechamento())->toBe(35.80);
    expect($cotacao->precoAbertura())->toBe(35.20);
    expect($cotacao->precoMaximo())->toBe(36.50);
    expect($cotacao->precoMinimo())->toBe(34.80);
    expect($cotacao->tipoMercado())->toBe('padrao');
    expect($cotacao->codBdi())->toBe('02');
});

test('parse line converts prices dividing by 100', function () {
    $parser = new CotahistParserService;
    $cotacao = $parser->parseLine(sampleLine());

    // 0000000003580 = 3580 / 100 = 35.80
    expect($cotacao->precoFechamento())->toBe(35.80);
});

test('parse line filters out header records (type 00)', function () {
    $parser = new CotahistParserService;
    $line = '00COTAHIST.2026'.str_repeat(' ', 230);

    expect($parser->parseLine($line))->toBeNull();
});

test('parse line filters out trailer records (type 99)', function () {
    $parser = new CotahistParserService;
    $line = '99COTAHIST.2026'.str_repeat(' ', 230);

    expect($parser->parseLine($line))->toBeNull();
});

test('parse line filters out invalid BDI codes', function () {
    $parser = new CotahistParserService;

    // BDI 12 is not in [02, 96]
    expect($parser->parseLine(sampleLine(bdi: '12')))->toBeNull();
});

test('parse line accepts BDI 96 (fractional)', function () {
    $parser = new CotahistParserService;
    $cotacao = $parser->parseLine(sampleLine(ticker: 'PETR4F      ', bdi: '96', tpmerc: '020'));

    expect($cotacao)->not->toBeNull();
    expect($cotacao->tipoMercado())->toBe('fracionario');
});

test('parse line filters out invalid market types', function () {
    $parser = new CotahistParserService;

    // Market type 030 is not in [010, 020]
    expect($parser->parseLine(sampleLine(tpmerc: '030')))->toBeNull();
});

test('parse line returns null for short lines', function () {
    $parser = new CotahistParserService;

    expect($parser->parseLine('short line'))->toBeNull();
    expect($parser->parseLine(''))->toBeNull();
});

test('parse lines processes multiple lines', function () {
    $parser = new CotahistParserService;
    $lines = [
        '00COTAHIST'.str_repeat(' ', 235),
        sampleLine('PETR4       '),
        sampleLine('VALE3       '),
        sampleLine(bdi: '12'),  // filtered out
        '99TRAILER'.str_repeat(' ', 236),
    ];

    $cotacoes = $parser->parseLines($lines);

    expect($cotacoes)->toHaveCount(2);
    expect($cotacoes[0]->ticker())->toBe('PETR4');
    expect($cotacoes[1]->ticker())->toBe('VALE3');
});

test('parse sample file from fixtures', function () {
    $parser = new CotahistParserService;
    $fixturePath = dirname(__DIR__, 4).'/fixtures/cotahist_sample.txt';
    $lines = file($fixturePath, FILE_IGNORE_NEW_LINES);

    $cotacoes = $parser->parseLines($lines);

    // 7 detail lines: 5 BDI=02/TPMERC=010, 1 BDI=96/TPMERC=020, 1 BDI=12 (filtered)
    expect($cotacoes)->toHaveCount(6);

    $tickers = array_map(fn ($c) => $c->ticker(), $cotacoes);
    expect($tickers)->toContain('PETR4');
    expect($tickers)->toContain('VALE3');
    expect($tickers)->toContain('WEGE3');
});
