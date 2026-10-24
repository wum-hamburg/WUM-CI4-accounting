<?php

$envFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
$vars = [
    'hostname' => 'localhost',
    'database' => 'wum-ci4-accounting',
    'username' => 'root',
    'password' => '',
    'port' => 3306,
];

if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        $value = trim($value, "\"'");
        match ($key) {
            'database.default.hostname' => $vars['hostname'] = $value,
            'database.default.database' => $vars['database'] = $value,
            'database.default.username' => $vars['username'] = $value,
            'database.default.password' => $vars['password'] = $value,
            'database.default.port' => $vars['port'] = (int) $value,
            default => null,
        };
    }
}

$mysqli = new mysqli($vars['hostname'], $vars['username'], $vars['password'], $vars['database'], $vars['port']);
$res = $mysqli->query("SELECT username, rights, password FROM user WHERE username='demo' LIMIT 1");
$row = $res ? $res->fetch_assoc() : null;
if ($row === null) {
    echo "no demo user\n";
    exit(1);
}

$candidates = ['demo', 'tz67', 'we89', 'fp23', 'wumy', 'Demo', 'TZ67'];
foreach ($candidates as $pw) {
    echo $pw . ': ' . (password_verify($pw, $row['password']) ? 'OK' : 'no') . PHP_EOL;
}
echo 'rights=' . $row['rights'] . PHP_EOL;
