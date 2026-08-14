<?php

ini_set('display_errors', '0');
ini_set('log_errors', '1');

function start_secure_session()
{
    if (session_status() == PHP_SESSION_ACTIVE)
        return;

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off';
    $secure_cookie = $https || getenv('SESSION_COOKIE_SECURE') == '1';

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure_cookie,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();

    if (isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > 1800) {
        $_SESSION = [];
        session_regenerate_id(true);
    }

    $_SESSION['last_activity'] = time();

    if (!headers_sent()) {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: same-origin');

        if ($secure_cookie)
            header('Strict-Transport-Security: max-age=31536000');
    }
}

function csrf_token()
{
    if (empty($_SESSION['csrf_token']))
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    return $_SESSION['csrf_token'];
}

function csrf_field()
{
    return '<input type="hidden" name="csrf_token" value="'
        . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function csrf_is_valid()
{
    return isset($_POST['csrf_token'])
        && is_string($_POST['csrf_token'])
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

function base64url_encode($value)
{
    return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
}

function base64url_decode($value)
{
    $padding = strlen($value) % 4;
    if ($padding)
        $value .= str_repeat('=', 4 - $padding);

    return base64_decode(strtr($value, '-_', '+/'), true);
}

function chat_token_secret()
{
    $secret = getenv('CHAT_TOKEN_SECRET');

    if (!$secret || strlen($secret) < 32)
        throw new RuntimeException('CHAT_TOKEN_SECRET must be at least 32 characters.');

    return $secret;
}

function create_chat_token($user_id, $chat_id)
{
    $payload = json_encode([
        'user_id' => (int) $user_id,
        'chat_id' => (int) $chat_id,
        'expires_at' => time() + 300
    ]);
    $encoded_payload = base64url_encode($payload);
    $signature = hash_hmac('sha256', $encoded_payload, chat_token_secret(), true);

    return $encoded_payload . '.' . base64url_encode($signature);
}

function verify_chat_token($token)
{
    if (!is_string($token))
        return null;

    $parts = explode('.', $token, 2);

    if (count($parts) != 2)
        return null;

    $expected = hash_hmac('sha256', $parts[0], chat_token_secret(), true);
    $signature = base64url_decode($parts[1]);

    if ($signature === false || !hash_equals($expected, $signature))
        return null;

    $payload_json = base64url_decode($parts[0]);
    $payload = json_decode($payload_json, true);

    if (!is_array($payload)
        || empty($payload['user_id'])
        || empty($payload['chat_id'])
        || empty($payload['expires_at'])
        || $payload['expires_at'] < time())
        return null;

    return [
        'user_id' => (int) $payload['user_id'],
        'chat_id' => (int) $payload['chat_id']
    ];
}

function websocket_origin_is_allowed($origin)
{
    $configured = getenv('APP_ORIGINS');
    $allowed = $configured
        ? array_map('trim', explode(',', $configured))
        : ['http://localhost:8080', 'http://127.0.0.1:8080'];

    return in_array($origin, $allowed, true);
}

function public_http_target($url)
{
    if (!is_string($url)
        || strlen($url) > 2048
        || !filter_var($url, FILTER_VALIDATE_URL))
        return null;

    $parts = parse_url($url);

    if (!$parts || !isset($parts['scheme']) || !isset($parts['host']))
        return null;

    $scheme = strtolower($parts['scheme']);

    if ($scheme != 'http' && $scheme != 'https')
        return null;

    if (isset($parts['user']) || isset($parts['pass']))
        return null;

    $port = isset($parts['port']) ? (int) $parts['port'] : ($scheme == 'https' ? 443 : 80);

    if (($scheme == 'http' && $port != 80) || ($scheme == 'https' && $port != 443))
        return null;

    $host = strtolower($parts['host']);
    if ($host == 'localhost' || substr($host, -6) == '.local')
        return null;

    if (filter_var($host, FILTER_VALIDATE_IP))
        $addresses = [$host];
    else
        $addresses = gethostbynamel($host);

    if (!$addresses)
        return null;

    foreach ($addresses as $address) {
        if (!filter_var(
            $address,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ))
            return null;
    }

    return [
        'host' => $host,
        'port' => $port,
        'ip' => $addresses[0]
    ];
}

function is_http_url($url)
{
    if (!is_string($url) || !filter_var($url, FILTER_VALIDATE_URL))
        return false;

    $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
    return $scheme == 'http' || $scheme == 'https';
}
