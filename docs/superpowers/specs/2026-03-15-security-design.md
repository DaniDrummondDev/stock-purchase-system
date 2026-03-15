# Security Design Spec — OWASP Top 10 2025

> **Date:** 2026-03-15
> **Status:** Draft
> **Context:** Stock Purchase System (Compra Programada de Ações)

## 1. Overview

Implementação de segurança abrangente baseada no OWASP Top 10 2025, cobrindo autenticação (Argon2id + 2FA), autorização (RBAC granular), protecção de API (rate limiting, IP blacklisting, threat detection), security headers, CORS restritivo, session hardening, SSRF protection, e security event logging com alertas automáticos.

### Decisões-chave

- **Password hashing:** Argon2id (substitui bcrypt) com password policy forte + HIBP check
- **Autenticação:** Sanctum dual-mode (cookie SPA + API tokens) + JWT (firebase/php-jwt) para integrações externas
- **2FA:** TOTP (pragmarx/google2fa-laravel) — obrigatório para admin/analyst/auditor, opcional para client
- **RBAC:** spatie/laravel-permission — 4 roles, 30+ permissions granulares, policies por resource
- **Rate limiting:** Por role + endpoint, progressive throttle no login, IP blacklisting automático
- **Security BC:** Bounded Context dedicado dentro da arquitectura DDD
- **Security events:** Tabela dedicada com severity classification e alertas automáticos

### Packages

| Package | Propósito |
|---------|-----------|
| `laravel/sanctum` | Already installed — SPA auth + API tokens |
| `spatie/laravel-permission` | RBAC — roles e permissions |
| `pragmarx/google2fa-laravel` | TOTP 2FA |
| `firebase/php-jwt` | JWT lightweight para integrações externas |

---

## 2. OWASP Top 10 2025 — Mapping

| # | OWASP 2025 | Implementation |
|---|-----------|----------------|
| A01 | Broken Access Control | RBAC com spatie/laravel-permission, policies por resource, middleware authorize em todas as rotas, data isolation (client vê apenas own data) |
| A02 | Cryptographic Failures | Argon2id para passwords, AES-256-GCM para API keys via Laravel `encrypted` cast (`ai_configurations.api_key`, `data_provider_configs.api_key`), TLS enforced (ForceHttpsMiddleware), session encryption enabled. CRUD access to encrypted credentials restricted to `ai.config.manage` permission (admin only) |
| A03 | Injection | Eloquent ORM (parameterized queries), Form Requests com validation, Content-Type enforcement no middleware |
| A04 | Insecure Design | Security como BC dedicado, threat model documentado, CQRS separation of concerns, principle of least privilege |
| A05 | Security Misconfiguration | SecurityHeadersMiddleware (CSP, HSTS, X-Frame-Options), CORS restritivo, APP_DEBUG=false em prod, config validation artisan command |
| A06 | Vulnerable Components | composer audit no CI, dependabot alerts, versões fixas no composer.lock |
| A07 | Authentication Failures | Argon2id, TOTP 2FA, progressive throttling, account lockout após 10 falhas, security event logging |
| A08 | Data Integrity Failures | CSRF tokens (Livewire auto), signed URLs, JWT signature verification (RS256), audit trail em todos os Eloquent models |
| A09 | Security Logging & Monitoring | security_events table dedicada, alertas automáticos, IP tracking, anomaly detection, X-Request-Id correlation |
| A10 | SSRF | URL whitelist para data providers externos, reject IPs privados, reject non-HTTPS (prod), response size limit |

---

## 3. Architecture — Security Bounded Context

### 3.1 Folder Structure

```
Domain/Security/
├── Authentication/
│   ├── Entities/
│   │   └── UserAccount.php
│   ├── ValueObjects/
│   │   ├── HashedPassword.php
│   │   └── TotpSecret.php
│   ├── Services/
│   │   ├── AuthenticationService.php
│   │   └── TwoFactorService.php
│   └── Events/
│       ├── UserLoggedIn.php
│       ├── UserLoggedOut.php
│       ├── LoginFailed.php
│       └── TwoFactorEnabled.php
├── Authorization/
│   ├── Enums/
│   │   ├── Role.php
│   │   └── Permission.php
│   ├── Policies/
│   │   ├── ClientePolicy.php
│   │   ├── CestaPolicy.php
│   │   ├── CompraPolicy.php
│   │   └── AiPolicy.php
│   └── Services/
│       └── RbacService.php
├── RateLimiting/
│   ├── Services/
│   │   └── RateLimitService.php
│   └── ValueObjects/
│       └── RateLimitConfig.php
├── ThreatDetection/
│   ├── Services/
│   │   ├── IpBlacklistService.php
│   │   └── AnomalyDetector.php
│   └── Events/
│       ├── SuspiciousActivityDetected.php
│       └── IpBlacklisted.php
└── Audit/
    ├── Entities/
    │   └── SecurityEvent.php
    ├── Services/
    │   └── SecurityEventLogger.php
    └── Events/
        └── SecurityAlertTriggered.php
```

### 3.2 Middleware Stack

Executed in order on every API request:

```
Request
  → SecurityHeadersMiddleware        (A05: security headers)
  → ForceHttpsMiddleware             (A02: TLS enforced)
  → IpBlacklistMiddleware            (A09/A07: reject blocked IPs)
  → RateLimitMiddleware              (A07: throttle by role/IP)
  → AuthenticateMiddleware           (A07: Sanctum cookie or token)
  → Enforce2FAMiddleware             (A07: verify 2FA if required)
  → AuthorizeMiddleware              (A01: RBAC + policies)
  → AuditRequestMiddleware           (A09: log authenticated requests)
→ Controller
```

Livewire frontend passes through the same stack with session/cookie auth (Sanctum SPA mode). External API uses Bearer token (Sanctum) or JWT.

---

## 4. Authentication

### 4.1 Password Hashing — Argon2id

```php
// config/hashing.php
'driver' => 'argon2id',
'argon' => [
    'memory'  => 19456,    // 19 MiB (OWASP recommended minimum)
    'threads' => 1,
    'time'    => 2,        // 2 iterations
],
```

**Note:** These values follow OWASP's recommended Argon2id minimum (19 MiB, 2 iterations, 1 thread). Each login uses ~19 MB RAM for hashing — safe for concurrent requests under PHP-FPM. For higher-security deployments, increase `memory` to 65536 (64 MiB) with `threads=4, time=4`, but ensure server has sufficient RAM (64 MB × max concurrent logins).

**Password policy** (enforced via Form Request):
- Minimum 12 characters
- At least 1 uppercase, 1 lowercase, 1 number, 1 symbol
- Cannot contain user's name or email
- Checked against Have I Been Pwned API via `Laravel\Validation\Rules\Password::defaults()`
- Auto-rehash on login if Argon2id parameters changed

### 4.2 Sanctum Dual-Mode

**SPA Mode (Livewire):**
- Login via `POST /auth/login` → creates session + CSRF cookie
- Session encrypted in Redis, TTL 2h
- CSRF token validated automatically by Livewire
- Cookie: `HttpOnly`, `Secure`, `SameSite=Lax` (Sanctum recommended — `Strict` breaks OAuth redirects)

**API Token Mode (external consumption):**
- `POST /api/auth/token` → returns Sanctum personal access token
- Token with `abilities` mapped to RBAC permissions (ex: `['client.view', 'portfolio.read']`)
- Expiration: configurable (default 24h)
- Refresh: `POST /api/auth/token/refresh`
- Revoke: `DELETE /api/auth/token` (current) or `DELETE /api/auth/tokens` (all)

### 4.3 JWT for External Integrations

**Use case:** JWT is needed for scenarios where the consumer cannot query the `personal_access_tokens` table — specifically: (1) future mobile app that needs stateless token verification at an API gateway, (2) Kafka consumers that validate message origin via JWT signature, (3) webhook callbacks where a third-party verifies our signed payload using our public key. Sanctum tokens require a DB lookup per request, making them unsuitable for these cases.

**Note:** The JTI blacklist in Redis means revocation is possible but JWT is not fully stateless. This is an acceptable trade-off — most requests are verified without Redis, revocation only checks Redis for the short TTL window.

Custom guard `jwt` using `firebase/php-jwt`:
- Algorithm: RSA-256 (RS256) — private key signs, public key verifies
- Claims: `sub` (user_id), `roles`, `permissions`, `iat`, `exp` (1h), `jti` (unique ID for revocation)
- JTI blacklist in Redis (TTL = remaining token time) for revocation
- Endpoint: `POST /api/auth/jwt` → returns `access_token` + `refresh_token` (7 days)

### 4.4 TOTP 2FA

Package: `pragmarx/google2fa-laravel`

- Setup: user generates QR code → scans with Google Authenticator/Authy → confirms with 6-digit code
- **Required** for roles: admin, analyst, auditor — enforced on first login via `Enforce2FAMiddleware`
- **Optional** for role: client — configurable in profile settings
- Recovery codes: 8 one-time codes generated on activation, hashed with Argon2id, each code works once only
- Tolerance window: 1 period (30s before/after) to compensate clock drift

### 4.5 Progressive Login Throttle

```
Attempt 1-3:   immediate response
Attempt 4:     delay 1s
Attempt 5:     delay 2s
Attempt 6:     delay 4s
Attempt 7:     delay 8s
Attempt 8:     delay 16s
Attempt 9:     delay 32s
Attempt 10:    lockout 30 min + email to user + security event
```

Counters tracked by **IP + email** (prevents distributed attack on same email across IPs, and vice-versa).

---

## 5. Authorization — RBAC

### 5.1 Roles

| Role | Description | 2FA | Scope |
|------|-------------|-----|-------|
| `admin` | Full system management | Required | Global |
| `analyst` | View data, manage baskets, execute purchase engine | Required | Global |
| `auditor` | Read-only on everything + security logs | Required | Global |
| `client` | Own portfolio, AI chat, personal settings | Optional | Own data only |

### 5.2 Permissions

Format: `resource.action`

**Client BC:** `client.create`, `client.view`, `client.view.any`, `client.update`, `client.update.any`, `client.delete`

**Basket BC:** `basket.create`, `basket.update`, `basket.view`, `basket.history`

**PurchaseEngine BC:** `purchase.execute`, `purchase.view`, `purchase.view.any`

**Tax BC:** `tax.view`, `tax.view.any`, `tax.kafka.publish`

**Rebalancing BC:** `rebalancing.execute`, `rebalancing.view`

**MarketData BC:** `market.import`, `market.view`

**AI BC:** `ai.chat`, `ai.recommendation`, `ai.risk.view`, `ai.config.manage`

**Security BC:** `security.events.view`, `security.users.manage`, `security.roles.manage`, `security.ip.manage`

### 5.3 Role → Permission Matrix

| Permission | admin | analyst | auditor | client |
|-----------|-------|---------|---------|--------|
| `client.create` | x | x | | |
| `client.view.any` | x | x | x | |
| `client.view` (own) | x | x | x | x |
| `client.update.any` | x | | | |
| `client.update` (own) | x | | | x |
| `client.delete` | x | | | |
| `basket.create` | x | x | | |
| `basket.update` | x | x | | |
| `basket.view` | x | x | x | x |
| `basket.history` | x | x | x | |
| `purchase.execute` | x | x | | |
| `purchase.view` (own) | x | x | x | x |
| `purchase.view.any` | x | x | x | |
| `tax.view.any` | x | x | x | |
| `tax.view` (own) | x | x | x | x |
| `tax.kafka.publish` | x | x | | |
| `rebalancing.execute` | x | x | | |
| `rebalancing.view` | x | x | x | |
| `market.import` | x | x | | |
| `market.view` | x | x | x | x |
| `ai.chat` | x | x | | x |
| `ai.recommendation` | x | x | | |
| `ai.risk.view` (own) | x | x | x | x |
| `ai.config.manage` | x | | | |
| `security.events.view` | x | | x | |
| `security.users.manage` | x | | | |
| `security.roles.manage` | x | | | |
| `security.ip.manage` | x | | | |

### 5.4 Policies

Each BC has its own Policy. The Policy resolves: "can this user perform this action on this resource?"

```php
// Example: ClientePolicy
public function view(User $user, Cliente $cliente): bool
{
    return $user->hasPermissionTo('client.view.any')
        || ($user->hasPermissionTo('client.view') && $user->cliente_id === $cliente->id);
}
```

The `*.any` vs `*` distinction is key: `client.view.any` allows viewing any client (admin/analyst), `client.view` only allows viewing own data (client). This guarantees **data isolation**.

**Convention:** In all policy implementations, `*.any` implicitly grants `*` access. The permission matrix only shows the highest-level permission per role to avoid redundancy.

### 5.5 Seeding

`RolesAndPermissionsSeeder` (idempotent — safe to re-run). Default admin created from `.env` (`ADMIN_EMAIL`, `ADMIN_PASSWORD`).

---

## 6. Rate Limiting

### 6.1 Limits by Role and Endpoint

| Context | Limit | Window | Response |
|---------|-------|--------|----------|
| Login (`POST /auth/login`) | 5 attempts | 1 min | Progressive throttle (see 4.5) |
| Login (after 10 failures) | Lockout | 30 min | Account locked, email sent |
| API — role `client` | 60 req | 1 min | 429 Too Many Requests |
| API — role `analyst` | 120 req | 1 min | 429 |
| API — role `admin` | 300 req | 1 min | 429 |
| API — unauthenticated | 20 req | 1 min | 429 |
| Token creation | 3 req | 1 hour | 429 |
| Password reset | 3 req | 1 hour | 429 |
| AI Chat | 20 req | 1 min | 429 (protects LLM cost) |
| COTAHIST import | 2 req | 1 hour | 429 |

Implemented via `RateLimiter::for()` in `AppServiceProvider`, Redis backend. Implementation must use Lua scripting (`EVAL`) for atomic increment+expire to prevent race conditions between `INCR` and `EXPIRE` commands. Laravel's `RedisStore` handles this correctly in recent versions, but should be verified.

---

## 7. Threat Detection

### 7.1 IP Blacklisting

**Table `ip_blacklist`:**

| Column | Type | Description |
|--------|------|-------------|
| id | uuid | PK |
| ip_address | varchar(45) | IPv4 or IPv6 |
| reason | varchar(100) | 'brute_force', 'scanning', 'rate_limit_abuse', 'manual' |
| blocked_until | timestamp (nullable) | Null = permanent |
| created_by | uuid (nullable) | FK → users (if manual block) |
| created_at | timestamp | — |
| updated_at | timestamp | — |

Index: `(ip_address)` unique

**Automatic block triggers:**

| Pattern | Action | Duration |
|---------|--------|----------|
| 15+ login failures in 5 min from same IP | Block IP | 1 hour |
| 50+ 404 requests in 1 min (scanning) | Block IP | 6 hours |
| 100+ rate limit hits in 5 min | Block IP | 24 hours |
| 3 temporary blocks on same IP | Block IP | Permanent (until manual review) |

`IpBlacklistMiddleware` checks Redis (cached from table) — O(1), no DB query per request.

### 7.2 Anomaly Detection

`AnomalyDetector` service analyzes patterns and dispatches `SuspiciousActivityDetected` event:

| Anomaly | Detection | Alert |
|---------|-----------|-------|
| Login from new IP | IP not in user's last 10 logins | Email to user + security event |
| Login from different country | GeoIP lookup (MaxMind GeoLite2 DB) | Email to user + admin notification |
| Privilege escalation | Role or permission changed | Immediate notification to all admins |
| Off-hours access | Login between 00h-06h in user's timezone (default: `America/Sao_Paulo`) | Security event (low priority) |
| Mass data access | User makes 50+ distinct reads in 5 min | Security event + admin notification |

---

## 8. Security Headers and CORS

### 8.1 Security Headers

`SecurityHeadersMiddleware` injects on **all** responses:

| Header | Value | OWASP |
|--------|-------|-------|
| `Strict-Transport-Security` | `max-age=31536000; includeSubDomains; preload` | A02 |
| `X-Content-Type-Options` | `nosniff` | A05 |
| `X-Frame-Options` | `DENY` | A05 |
| `X-XSS-Protection` | `0` (deprecated, CSP replaces) | A05 |
| `Content-Security-Policy` | `default-src 'self'; script-src 'self' 'nonce-{random}'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; connect-src 'self'; frame-ancestors 'none'` | A05 |
| `Referrer-Policy` | `strict-origin-when-cross-origin` | A05 |
| `Permissions-Policy` | `camera=(), microphone=(), geolocation=(), payment=()` | A05 |
| `X-Request-Id` | UUID per request | A09 |

CSP nonce generated per request and passed to Blade/Livewire views via `@nonce` directive for legitimate inline scripts.

**Livewire 3 caveat:** Livewire injects inline scripts that require CSP nonce support. Use `@livewireScriptConfig(['nonce' => csp_nonce()])` in the layout. If CSP conflicts arise during implementation, fallback to adding `'unsafe-eval'` to `script-src` (less secure but pragmatic). Test CSP thoroughly with Livewire before production deployment.

### 8.2 CORS — Restrictive

```php
// config/cors.php
'paths'                    => ['api/*', 'sanctum/csrf-cookie'],
'allowed_methods'          => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
'allowed_origins'          => explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:8000')),
'allowed_origins_patterns' => [],
'allowed_headers'          => ['Content-Type', 'Authorization', 'X-Requested-With', 'Accept', 'X-XSRF-TOKEN'],
'exposed_headers'          => ['X-Request-Id'],
'max_age'                  => 7200,
'supports_credentials'     => true,
```

Production: `CORS_ALLOWED_ORIGINS` contains only the frontend domain(s). Never `*`.

---

## 9. Session Hardening

| Setting | Current | New | Reason |
|---------|---------|-----|--------|
| `encrypt` | `false` | `true` | A02: session data encrypted |
| `lifetime` | 120 min | 120 min | Reasonable for normal use |
| `expire_on_close` | `false` | `true` | Session dies when browser closes |
| `same_site` | `lax` | `lax` (keep) | A08: `strict` breaks Sanctum SPA cookie on external links/OAuth redirects. `lax` is Sanctum's recommended setting |
| `secure` | not set | `true` (prod) | A02: cookie only via HTTPS |
| `http_only` | `true` | `true` | A03: JS cannot access cookie |
| `partitioned` | `false` | `true` | Privacy: partitioned cookie (CHIPS) |

Session ID regenerated on every login (`session()->regenerate()`) and on privilege escalation. Prevents session fixation (A07).

---

## 10. SSRF Protection (A10)

For data providers making external HTTP requests (BcbProvider, future providers):

**URL Whitelist:**
```php
// config/security.php
'allowed_external_hosts' => [
    'api.bcb.gov.br',
    'olinda.bcb.gov.br',
],
```

**Validations in `DataProviderManager`:**
- Reject any URL resolving to private IP (10.x, 172.16-31.x, 192.168.x, 127.x, ::1)
- Reject non-`https://` protocols (except in `APP_ENV=local`)
- Reject redirects to non-whitelisted hosts
- Maximum timeout: 10s per external request
- Response size limit: 5MB

---

## 11. Security Event Logging

### 11.1 Table `security_events`

| Column | Type | Description |
|--------|------|-------------|
| id | uuid | PK |
| event_type | varchar(50) | Enum: login, logout, login_failed, password_changed, 2fa_enabled, 2fa_disabled, token_created, token_revoked, permission_changed, ip_blocked, suspicious_activity, access_denied |
| severity | varchar(10) | 'critical', 'high', 'medium', 'low' |
| user_id | uuid (nullable) | FK → users (null if unauthenticated) |
| ip_address | varchar(45) | IPv4/IPv6 |
| user_agent | varchar(500) | Browser/client info |
| request_id | uuid | Correlates with X-Request-Id header |
| resource | varchar(100) | Affected resource (ex: 'user:uuid', 'cliente:uuid') |
| details | jsonb | Extra context (ex: `{"old_role": "client", "new_role": "admin"}`) |
| created_at | timestamp | — |

Indexes: `(event_type, created_at)`, `(user_id, created_at)`, `(ip_address, created_at)`, `(severity, created_at)`

### 11.2 Severity Classification

| Severity | Events | Retention |
|----------|--------|-----------|
| Critical | permission_changed, suspicious_activity (escalation), ip_blocked (permanent) | 2 years |
| High | login_failed (lockout), access_denied (repeated), 2fa_disabled | 1 year |
| Medium | login, logout, password_changed, 2fa_enabled, token_created/revoked | 6 months |
| Low | login (success normal), access_denied (single) | 3 months |

Scheduled job `SecurityEventCleanup` — runs daily, removes expired events per retention policy.

**Performance:** For high-traffic scenarios, the `security_events` table uses PostgreSQL range partitioning by `created_at` (monthly partitions). Events are written to a Redis list buffer first and flushed to the database in batches every 30 seconds via a scheduled command, reducing write pressure.

### 11.3 Automatic Alerts

| Trigger | Recipient | Channel |
|---------|-----------|---------|
| Login lockout (10 failures) | Affected user | Email: "Your account has been temporarily locked" |
| Login from new IP | Affected user | Email: "New login detected from {ip}" |
| IP blacklisted automatically | All admins | In-app + email |
| Privilege escalation | All admins | In-app + email (immediate) |
| 2FA disabled | User + admins | Email |
| Mass data access detected | All admins | In-app + email |
| 5+ access_denied from same user in 1 min | Admins | In-app |

---

## 12. API Endpoints — Auth & Security

### 12.1 Authentication

| Method | Path | Description | Auth |
|--------|------|-------------|------|
| POST | `/auth/login` | Login (SPA session) | Public |
| POST | `/auth/logout` | Logout | Authenticated |
| POST | `/auth/register` | Register new user | Public |
| POST | `/auth/forgot-password` | Send reset email | Public |
| POST | `/auth/reset-password` | Reset password with token | Public |
| POST | `/api/auth/token` | Create Sanctum API token | Authenticated |
| POST | `/api/auth/token/refresh` | Refresh API token | Authenticated |
| DELETE | `/api/auth/token` | Revoke current token | Authenticated |
| DELETE | `/api/auth/tokens` | Revoke all tokens | Authenticated |
| POST | `/api/auth/jwt` | Create JWT access + refresh tokens | Authenticated |
| POST | `/api/auth/jwt/refresh` | Refresh JWT | JWT refresh token |

### 12.2 Two-Factor Authentication

| Method | Path | Description | Auth |
|--------|------|-------------|------|
| POST | `/api/auth/2fa/setup` | Generate TOTP secret + QR code | Authenticated |
| POST | `/api/auth/2fa/confirm` | Confirm 2FA with code | Authenticated |
| DELETE | `/api/auth/2fa` | Disable 2FA | Authenticated + 2FA verified |
| POST | `/api/auth/2fa/verify` | Verify TOTP code during login | Authenticated (pre-2FA) |
| POST | `/api/auth/2fa/recovery` | Use recovery code | Authenticated (pre-2FA) |

### 12.3 User & Role Management

| Method | Path | Description | Permission |
|--------|------|-------------|------------|
| GET | `/api/security/users` | List users | security.users.manage |
| PUT | `/api/security/users/{id}/roles` | Assign roles | security.roles.manage |
| GET | `/api/security/roles` | List roles + permissions | security.roles.manage |
| GET | `/api/security/events` | List security events (paginated, filterable) | security.events.view |
| GET | `/api/security/ip-blacklist` | List blocked IPs | security.ip.manage |
| POST | `/api/security/ip-blacklist` | Manually block IP | security.ip.manage |
| DELETE | `/api/security/ip-blacklist/{id}` | Unblock IP | security.ip.manage |

### 12.4 Livewire Components

| Component | Description | Access |
|-----------|-------------|--------|
| `Security/LoginForm` | Login with 2FA support | Public |
| `Security/RegisterForm` | Registration with password policy | Public |
| `Security/TwoFactorSetup` | QR code + confirmation flow | Authenticated |
| `Security/SecurityDashboard` | Events, IPs, anomalies overview | admin, auditor |
| `Security/UserManagement` | Users, roles, permissions CRUD | admin |
| `Security/ProfileSecurity` | Password change, 2FA, active sessions | Authenticated |

---

## 13. Data Model — New Tables and Alterations

### 13.1 Alterations to `users` table

**Prerequisite migration:** Convert `users.id` from auto-incrementing bigint to UUID to align with CLAUDE.md convention ("IDs: UUID for all primary keys"). Also update `sessions.user_id` and `personal_access_tokens.tokenable_id` FK references. This migration runs before any security columns are added.

**New columns:**

| Column | Type | Description |
|--------|------|-------------|
| cliente_id | uuid (nullable) | FK → clientes. Links user to client for data isolation. Null for admin/analyst/auditor |
| two_factor_secret | text (encrypted) | TOTP secret |
| two_factor_confirmed_at | timestamp (nullable) | When 2FA was confirmed |
| two_factor_recovery_codes | text (encrypted) | JSON array of hashed recovery codes |
| failed_login_attempts | integer, default 0 | Consecutive failure counter |
| locked_until | timestamp (nullable) | Lockout until this date |
| last_login_at | timestamp (nullable) | Last successful login |
| last_login_ip | varchar(45) | Last login IP (IPv4/IPv6) |

Index: `(cliente_id)` — for policy lookups

**User model architecture:** The `User` Eloquent model moves to `Infrastructure/Persistence/Models/User.php` within the Security BC. `Domain/Security/Authentication/Entities/UserAccount.php` is a pure domain entity (no Eloquent) used within the domain layer. The infrastructure `User` model maps to/from `UserAccount`, following the same pattern as `Cliente` entity ↔ `Cliente` Eloquent model.

### 13.2 New table: `ip_blacklist`

| Column | Type | Description |
|--------|------|-------------|
| id | uuid | PK |
| ip_address | varchar(45) | IPv4 or IPv6 |
| reason | varchar(100) | 'brute_force', 'scanning', 'rate_limit_abuse', 'manual' |
| blocked_until | timestamp (nullable) | Null = permanent |
| created_by | uuid (nullable) | FK → users (if manual) |
| created_at | timestamp | — |
| updated_at | timestamp | — |

Unique index: `(ip_address)`

### 13.3 New table: `security_events`

See Section 11.1 for full schema.

### 13.4 spatie/laravel-permission tables (auto-migrated)

- `roles` — id, name, guard_name
- `permissions` — id, name, guard_name
- `model_has_roles` — role_id, model_type, model_id
- `model_has_permissions` — permission_id, model_type, model_id
- `role_has_permissions` — permission_id, role_id

---

## 14. Configuration

### 14.1 New config file: `config/security.php`

```php
return [
    // SSRF Protection
    'allowed_external_hosts' => [
        'api.bcb.gov.br',
        'olinda.bcb.gov.br',
    ],

    // Rate Limiting
    'rate_limits' => [
        'login'           => ['attempts' => 5, 'window' => 60],
        'lockout_minutes'  => 30,
        'api_client'      => ['attempts' => 60, 'window' => 60],
        'api_analyst'     => ['attempts' => 120, 'window' => 60],
        'api_admin'       => ['attempts' => 300, 'window' => 60],
        'api_guest'       => ['attempts' => 20, 'window' => 60],
        'token_creation'  => ['attempts' => 3, 'window' => 3600],
        'password_reset'  => ['attempts' => 3, 'window' => 3600],
        'ai_chat'         => ['attempts' => 20, 'window' => 60],
        'cotahist_import' => ['attempts' => 2, 'window' => 3600],
    ],

    // IP Blacklisting
    'ip_blacklist' => [
        'login_failures_threshold'    => 15,
        'login_failures_window'       => 300,
        'scanning_404_threshold'      => 50,
        'scanning_404_window'         => 60,
        'rate_limit_abuse_threshold'  => 100,
        'rate_limit_abuse_window'     => 300,
        'permanent_after_temp_blocks' => 3,
    ],

    // Anomaly Detection
    'anomaly' => [
        'new_ip_alert'           => true,
        'geo_alert'              => true,
        'off_hours_start'        => '00:00',
        'off_hours_end'          => '06:00',
        'mass_access_threshold'  => 50,
        'mass_access_window'     => 300,
    ],

    // Security Event Retention (days)
    'retention' => [
        'critical' => 730,
        'high'     => 365,
        'medium'   => 180,
        'low'      => 90,
    ],

    // 2FA
    'two_factor' => [
        'required_roles'   => ['admin', 'analyst', 'auditor'],
        'recovery_codes'   => 8,
        'window'           => 1,
    ],
];
```

### 14.2 New .env variables

```env
# CORS
CORS_ALLOWED_ORIGINS=http://localhost:8000

# JWT
JWT_PRIVATE_KEY_PATH=storage/jwt/private.pem
JWT_PUBLIC_KEY_PATH=storage/jwt/public.pem
JWT_TTL=3600
JWT_REFRESH_TTL=604800

# Admin seed
ADMIN_EMAIL=admin@sps.local
ADMIN_PASSWORD=

# Security
SESSION_ENCRYPT=true
```

---

## 15. Testing Strategy

### 15.1 Unit Tests
- `HashedPassword` VO — Argon2id hashing and verification
- `TotpSecret` VO — secret generation, code validation
- `RateLimitConfig` VO — configuration parsing
- `AnomalyDetector` — pattern matching with mocked data
- `IpBlacklistService` — threshold evaluation
- Each Policy — permission checks for all combinations of role × action

### 15.2 Feature Tests
- Login flow: success, failure, lockout, progressive throttle
- 2FA flow: setup, confirm, verify, recovery codes, enforcement
- Token management: create, refresh, revoke, abilities
- JWT flow: create, verify signature, refresh, blacklist
- Rate limiting: per role, per endpoint, 429 responses
- IP blacklisting: auto-block, manual block/unblock
- RBAC: each role can only access permitted resources
- Data isolation: client A cannot see client B data
- Security headers present in responses
- CORS: blocked origins rejected

### 15.3 Integration Tests
- Full login → 2FA → API access flow
- Security event creation and alert dispatch
- IP blacklist cache invalidation

---

## 16. Sprint Mapping

Security integrates as a new Sprint 1.5 (before the existing Sprints depend on auth):

| Sprint | Component | Dependencies |
|--------|-----------|-------------|
| Sprint 1.5a (Auth Foundation) | Argon2id config, User model alterations, Sanctum SPA + API token, login/logout/register endpoints, SecurityHeadersMiddleware, ForceHttpsMiddleware, CORS config, session hardening | Sprint 0 (Laravel setup) |
| Sprint 1.5b (RBAC + 2FA) | spatie/laravel-permission setup, roles/permissions seeder, policies per BC, Enforce2FAMiddleware, TwoFactorService, 2FA endpoints, JWT guard | Sprint 1.5a |
| Sprint 1.5c (Threat Detection) | RateLimitService, progressive throttle, IpBlacklistService + middleware, AnomalyDetector, security_events table + logger, SecurityAlertDispatcher, SecurityDashboard Livewire | Sprint 1.5b |

**Note:** Existing Sprint 1 API routes (clientes) will be updated to require authentication and authorization after Sprint 1.5a is complete. All subsequent sprints inherit the security layer automatically via middleware stack.

**Route migration example:**
```php
// Before (Sprint 1 — no auth)
Route::prefix('clientes')->group(function () {
    Route::post('/adesao', [ClienteController::class, 'aderir']);
    // ...
});

// After (Sprint 1.5a — secured)
Route::middleware(['auth:sanctum', 'enforce-2fa'])->prefix('clientes')->group(function () {
    Route::post('/adesao', [ClienteController::class, 'aderir'])->middleware('can:client.create');
    Route::get('/{clienteId}/carteira', [ClienteController::class, 'carteira'])->middleware('can:view,cliente');
    // ...
});
```
