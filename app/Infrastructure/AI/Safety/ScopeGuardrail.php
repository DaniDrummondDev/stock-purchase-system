<?php

declare(strict_types=1);

namespace App\Infrastructure\AI\Safety;

final class ScopeGuardrail
{
    private const FINANCIAL_KEYWORDS = [
        'ação', 'ações', 'carteira', 'investir', 'investimento', 'compra', 'venda',
        'cesta', 'rebalanceamento', 'custódia', 'preço médio', 'cotação', 'ticker',
        'bolsa', 'b3', 'dividendo', 'rendimento', 'rentabilidade', 'imposto', 'ir',
        'dedo-duro', 'kafka', 'risco', 'volatilidade', 'aporte', 'valor mensal',
        'simulação', 'projeção', 'portfolio', 'mercado', 'selic', 'ipca', 'câmbio',
        'lote', 'fracionário', 'top five', 'programada', 'cpf', 'cliente',
        'stock', 'share', 'market', 'price', 'volume', 'risk', 'tax',
    ];

    private const BLOCKED_PATTERNS = [
        '/\b(compre|venda|invista em)\s+\w{4,6}\d/i', // direct buy/sell recommendations
    ];

    public function isInScope(string $message): ScopeCheckResult
    {
        $lower = mb_strtolower($message);

        // Check for financial keywords
        foreach (self::FINANCIAL_KEYWORDS as $keyword) {
            if (str_contains($lower, $keyword)) {
                return new ScopeCheckResult(inScope: true, reason: 'financial_keyword_match');
            }
        }

        // Short messages that are greetings/small talk are ok (the LLM handles them)
        if (mb_strlen($message) < 30) {
            return new ScopeCheckResult(inScope: true, reason: 'short_message_allowed');
        }

        // Default: allow but flag as potentially off-topic
        return new ScopeCheckResult(inScope: true, reason: 'default_allow');
    }

    public function hasBlockedContent(string $message): ?string
    {
        foreach (self::BLOCKED_PATTERNS as $pattern) {
            if (preg_match($pattern, $message)) {
                return 'Não posso fornecer recomendações diretas de compra ou venda de ativos específicos.';
            }
        }

        return null;
    }
}
