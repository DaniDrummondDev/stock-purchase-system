# ADR-005: Security Aligned with OWASP Top 10 2025

## Status
Accepted

## Context
The system handles sensitive financial data including CPF (Brazilian tax ID), portfolio holdings, and transaction history. A comprehensive security posture aligned with industry standards is required to protect users and meet regulatory expectations.

## Decision
Implement all OWASP Top 10 2025 controls with the following specific measures:

- **Password hashing**: Argon2id (replacing bcrypt) — GPU/ASIC resistant, memory-hard.
- **Authorization**: RBAC via `spatie/laravel-permission` with 4 roles (admin, analyst, auditor, client) and 30+ granular permissions.
- **Two-Factor Authentication**: TOTP-based 2FA, mandatory for admin, analyst, and auditor roles.
- **Session/API security**: Sanctum dual-mode — SPA cookie authentication for Livewire frontend, API token authentication for programmatic access.
- **External integrations**: JWT via `firebase/php-jwt` for third-party service authentication.
- **Rate limiting**: Tiered per role to prevent abuse while accommodating legitimate usage patterns.
- **IP blacklisting**: With anomaly detection for suspicious access patterns.
- **Security event logging**: Automated alerts for critical security events (failed logins, privilege escalation attempts, anomalous activity).

## Consequences
### Positive
- Comprehensive security posture covering authentication, authorization, and monitoring.
- Argon2id provides superior resistance against GPU/ASIC brute-force attacks compared to bcrypt.
- Granular RBAC enables least-privilege access control across all system features.
- Automated alerting enables rapid incident response.

### Negative
- Significant implementation effort across multiple system layers.
- Mandatory 2FA adds friction for admin, analyst, and auditor users during onboarding.
- Rate limiting configuration requires balancing security with usability.

### Risks
- Overly aggressive rate limiting may block legitimate high-frequency API consumers.
- Anomaly detection false positives could trigger unnecessary alerts and alert fatigue.
- Argon2id memory cost parameter must be tuned to avoid excessive server memory usage under load.
