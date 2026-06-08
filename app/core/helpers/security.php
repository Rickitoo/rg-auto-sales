<?php

if (!function_exists('public_honeypot_input')) {
    function public_honeypot_input(string $field = 'website'): string
    {
        return '<div style="position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden;" aria-hidden="true">'
            . '<label>Website<input type="text" name="' . h($field) . '" tabindex="-1" autocomplete="off"></label>'
            . '</div>';
    }

    function public_abort(string $message, int $status = 422): void
    {
        http_response_code($status);
        die($message);
    }

    function public_request_ip(): string
    {
        return (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    }

    function public_rate_limit(string $bucket, int $maxAttempts = 5, int $windowSeconds = 300): void
    {
        $now = time();
        $key = hash('sha256', $bucket . '|' . public_request_ip() . '|' . session_id());

        if (!isset($_SESSION['_public_rate_limits']) || !is_array($_SESSION['_public_rate_limits'])) {
            $_SESSION['_public_rate_limits'] = [];
        }

        $attempts = $_SESSION['_public_rate_limits'][$key] ?? [];
        $attempts = array_values(array_filter($attempts, static fn($ts) => is_int($ts) && $ts > ($now - $windowSeconds)));

        if (count($attempts) >= $maxAttempts) {
            public_abort('Muitas tentativas. Aguarde alguns minutos e tente novamente.', 429);
        }

        $attempts[] = $now;
        $_SESSION['_public_rate_limits'][$key] = $attempts;
    }

    function public_validate_honeypot(string $field = 'website'): void
    {
        if (trim((string)($_POST[$field] ?? '')) !== '') {
            public_abort('Pedido invalido.', 400);
        }
    }

    function public_require_form_security(string $bucket, int $maxAttempts = 5, int $windowSeconds = 300): void
    {
        require_post_csrf();
        public_validate_honeypot();
        public_rate_limit($bucket, $maxAttempts, $windowSeconds);
    }

    function public_form_security_error(string $bucket, int $maxAttempts = 5, int $windowSeconds = 300): string
    {
        $token = $_POST['csrf_token'] ?? null;
        if (!csrf_verify(is_string($token) ? $token : null)) {
            return 'Sessao expirada. Atualize a pagina e tente novamente.';
        }

        if (trim((string)($_POST['website'] ?? '')) !== '') {
            return 'Pedido invalido.';
        }

        $now = time();
        $key = hash('sha256', $bucket . '|' . public_request_ip() . '|' . session_id());
        if (!isset($_SESSION['_public_rate_limits']) || !is_array($_SESSION['_public_rate_limits'])) {
            $_SESSION['_public_rate_limits'] = [];
        }

        $attempts = $_SESSION['_public_rate_limits'][$key] ?? [];
        $attempts = array_values(array_filter($attempts, static fn($ts) => is_int($ts) && $ts > ($now - $windowSeconds)));
        if (count($attempts) >= $maxAttempts) {
            return 'Muitas tentativas. Aguarde alguns minutos e tente novamente.';
        }

        $attempts[] = $now;
        $_SESSION['_public_rate_limits'][$key] = $attempts;

        return '';
    }

    function public_valid_email(string $email, bool $required = false): bool
    {
        if ($email === '') {
            return !$required;
        }

        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    function public_valid_phone(string $phone, bool $required = true): bool
    {
        if ($phone === '') {
            return !$required;
        }

        if (!preg_match('/^[0-9+\s().-]{7,25}$/', $phone)) {
            return false;
        }

        return strlen(preg_replace('/\D+/', '', $phone)) >= 7;
    }
}
