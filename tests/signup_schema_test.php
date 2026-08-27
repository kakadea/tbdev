<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$schema = file_get_contents($root . '/SQL/tb.sql');
$handler = file_get_contents($root . '/takesignup.php');
if ($schema === false || $handler === false) {
    fwrite(STDERR, "Unable to read signup schema or handler.\n");
    exit(1);
}

if (!preg_match('/CREATE TABLE users \((.*?)\) ENGINE=/s', $schema, $table_match)) {
    fwrite(STDERR, "Users table definition not found.\n");
    exit(1);
}

$required = array();
foreach (preg_split('/\R/', $table_match[1]) as $line) {
    if (preg_match('/^\s*([`A-Za-z_][`A-Za-z0-9_]*)\s+[^,]+NOT NULL,?\s*$/', $line, $column_match) && strpos($line, 'DEFAULT') === false && strpos($line, 'AUTO_INCREMENT') === false) {
        $required[] = trim($column_match[1], '`');
    }
}

$columns_start = strpos($handler, '$signup_columns =');
$insert_start = strpos($handler, '$ret = mysql_query', $columns_start === false ? 0 : $columns_start);
if ($columns_start === false || $insert_start === false) {
    fwrite(STDERR, "Signup column construction not found.\n");
    exit(1);
}
$columns_region = substr($handler, $columns_start, $insert_start - $columns_start);

foreach ($required as $column) {
    if (!preg_match("/['\"]" . preg_quote($column, '/') . "['\"] /", $columns_region) &&
        !preg_match("/['\"]" . preg_quote($column, '/') . "['\"](?:[,)]|\\s)/", $columns_region)) {
        fwrite(STDERR, "Signup does not explicitly populate required column: {$column}\n");
        exit(1);
    }
}

if (strpos($handler, 'password_hash') === false || strpos($handler, 'error_log(\'TBDev signup insert failed') === false) {
    fwrite(STDERR, "Signup modern password or safe DB diagnostic is missing.\n");
    exit(1);
}

echo "Signup schema coverage passed.\n";
