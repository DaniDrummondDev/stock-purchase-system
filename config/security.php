<?php

return [

    // SSRF Protection
    'allowed_external_hosts' => [
        'api.bcb.gov.br',
        'olinda.bcb.gov.br',
    ],

    // Rate Limiting
    'rate_limits' => [
        'login' => ['attempts' => 5, 'window' => 60],
        'lockout_minutes' => 30,
        'api_client' => ['attempts' => 60, 'window' => 60],
        'api_analyst' => ['attempts' => 120, 'window' => 60],
        'api_admin' => ['attempts' => 300, 'window' => 60],
        'api_guest' => ['attempts' => 20, 'window' => 60],
        'token_creation' => ['attempts' => 3, 'window' => 3600],
        'password_reset' => ['attempts' => 3, 'window' => 3600],
        'ai_chat' => ['attempts' => 20, 'window' => 60],
    ],

    // IP Blacklisting
    'ip_blacklist' => [
        'login_failures_threshold' => 15,
        'login_failures_window' => 300,
        'scanning_404_threshold' => 50,
        'scanning_404_window' => 60,
        'rate_limit_abuse_threshold' => 100,
        'rate_limit_abuse_window' => 300,
        'permanent_after_temp_blocks' => 3,
    ],

    // Security Event Retention (days)
    'retention' => [
        'critical' => 730,
        'high' => 365,
        'medium' => 180,
        'low' => 90,
    ],

    // 2FA
    'two_factor' => [
        'required_roles' => ['admin', 'analyst', 'auditor'],
        'recovery_codes' => 8,
        'window' => 1,
    ],

    // JWT
    'jwt' => [
        'ttl' => (int) env('JWT_TTL', 3600),
        'refresh_ttl' => (int) env('JWT_REFRESH_TTL', 604800),
    ],

    // Anomaly Detection
    'anomaly' => [
        'off_hours_start' => 0,
        'off_hours_end' => 6,
    ],

    // Password Policy
    'password' => [
        'min_length' => 12,
        'require_uppercase' => true,
        'require_lowercase' => true,
        'require_number' => true,
        'require_symbol' => true,
        'check_pwned' => env('PASSWORD_CHECK_PWNED', false),
    ],

];
