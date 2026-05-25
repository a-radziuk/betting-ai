<?php

/**
 * Dump database/database.sqlite as MySQL-compatible SQL.
 *
 * Usage:
 *   php database/sqlite_to_mysql_dump.php [output-file]
 *   php database/sqlite_to_mysql_dump.php [output-file] --data-only --exclude=foo,bar
 */

declare(strict_types=1);

$sqlitePath = __DIR__.'/database.sqlite';
$outputPath = __DIR__.'/database.mysql.sql';
$dataOnly = false;
$excludedTables = [];

foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--data-only') {
        $dataOnly = true;

        continue;
    }

    if (str_starts_with($arg, '--exclude=')) {
        $excludedTables = array_values(array_filter(array_map(
            static fn (string $table): string => trim($table),
            explode(',', substr($arg, strlen('--exclude=')))
        )));

        continue;
    }

    $outputPath = $arg;
}

if (! is_readable($sqlitePath)) {
    fwrite(STDERR, "SQLite database not found: {$sqlitePath}\n");
    exit(1);
}

$pdo = new PDO('sqlite:'.$sqlitePath, null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$out = fopen($outputPath, 'wb');
if ($out === false) {
    fwrite(STDERR, "Cannot write: {$outputPath}\n");
    exit(1);
}

fwrite($out, "-- MySQL dump generated from {$sqlitePath}\n");
fwrite($out, '-- Generated at: '.date('c')."\n\n");
fwrite($out, "SET NAMES utf8mb4;\n");
fwrite($out, "SET FOREIGN_KEY_CHECKS = 0;\n");
fwrite($out, "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n\n");

$tables = $pdo->query(
    "SELECT name FROM sqlite_master
     WHERE type = 'table' AND name NOT LIKE 'sqlite_%'
     ORDER BY name"
)->fetchAll(PDO::FETCH_COLUMN);
$tables = array_values(array_filter(
    $tables,
    static fn (string $table): bool => ! in_array($table, $excludedTables, true),
));

foreach ($tables as $table) {
    if (! $dataOnly) {
        $createSql = $pdo->query(
            "SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ".$pdo->quote($table)
        )->fetchColumn();

        if (! is_string($createSql) || $createSql === '') {
            continue;
        }

        fwrite($out, "DROP TABLE IF EXISTS `{$table}`;\n");
        fwrite($out, sqliteCreateToMysql($createSql, $table).";\n\n");
    }

    $columns = $pdo->query("PRAGMA table_info({$table})")->fetchAll();
    $columnNames = array_map(static fn (array $col): string => $col['name'], $columns);

    $rowCount = (int) $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
    if ($rowCount === 0) {
        fwrite($out, "\n");

        continue;
    }

    $select = $pdo->query('SELECT * FROM `'.$table.'`');
    $batch = [];
    $batchSize = 100;

    while ($row = $select->fetch(PDO::FETCH_ASSOC)) {
        $values = [];
        foreach ($columnNames as $name) {
            $values[] = mysqlValue($table, $name, $row[$name] ?? null);
        }
        $batch[] = '('.implode(', ', $values).')';

        if (count($batch) >= $batchSize) {
            writeInsert($out, $table, $columnNames, $batch);
            $batch = [];
        }
    }

    if ($batch !== []) {
        writeInsert($out, $table, $columnNames, $batch);
    }

    fwrite($out, "\n");
}

if (! $dataOnly) {
    $indexes = $pdo->query(
        "SELECT sql FROM sqlite_master
         WHERE type = 'index' AND sql IS NOT NULL AND name NOT LIKE 'sqlite_%'
         ORDER BY name"
    )->fetchAll(PDO::FETCH_COLUMN);

    if ($indexes !== []) {
        fwrite($out, "-- Indexes\n");
        foreach ($indexes as $indexSql) {
            if (! is_string($indexSql) || $indexSql === '') {
                continue;
            }
            fwrite($out, sqliteIndexToMysql($indexSql).";\n");
        }
        fwrite($out, "\n");
    }
}

fwrite($out, "SET FOREIGN_KEY_CHECKS = 1;\n");
fclose($out);

echo "Wrote {$outputPath} (".number_format(filesize($outputPath))." bytes)\n";

function sqliteCreateToMysql(string $sql, string $table): string
{
    $sql = preg_replace('/^CREATE TABLE IF NOT EXISTS /i', 'CREATE TABLE ', $sql) ?? $sql;
    $sql = str_replace('"', '`', $sql);

    $hadAutoincrementPk = (bool) preg_match(
        '/`(\w+)`\s+integer\s+primary\s+key\s+autoincrement/i',
        $sql
    );

    $sql = preg_replace(
        '/`(\w+)`\s+integer\s+primary\s+key\s+autoincrement\s+not\s+null/i',
        '`$1` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
        $sql
    ) ?? $sql;

    $sql = preg_replace(
        '/`(\w+)`\s+integer\s+primary\s+key\s+autoincrement/i',
        '`$1` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
        $sql
    ) ?? $sql;

    $sql = preg_replace('/\binteger\b/i', 'BIGINT', $sql) ?? $sql;
    $sql = preg_replace('/\bnumeric\b/i', 'DECIMAL(10, 4)', $sql) ?? $sql;
    $sql = preg_replace('/\bvarchar\b/i', 'VARCHAR(255)', $sql) ?? $sql;
    $sql = preg_replace('/\bdatetime\b/i', 'DATETIME', $sql) ?? $sql;
    $sql = preg_replace('/\btext\b/i', 'LONGTEXT', $sql) ?? $sql;
    $sql = preg_replace('/\bBIGINT UNSIGNED\b/i', 'BIGINT', $sql) ?? $sql;

    foreach (unixTimestampColumns($table) as $column) {
        $pattern = '/`'.preg_quote($column, '/').'`\s+BIGINT\b/i';
        $replacement = '`'.$column.'` DATETIME';
        $sql = preg_replace($pattern, $replacement, $sql) ?? $sql;
    }

    if ($hadAutoincrementPk) {
        $sql = preg_replace(
            '/,\s*primary\s+key\s*\(`id`\)/i',
            '',
            $sql
        ) ?? $sql;
    }

    $sql = preg_replace(
        '/,\s*primary\s+key\s*\(([^)]+)\)/i',
        ', PRIMARY KEY ($1)',
        $sql
    ) ?? $sql;

    $sql = preg_replace(
        '/`(\w+)`\s+BIGINT\s+not\s+null,\s*foreign\s+key/i',
        '`$1` BIGINT NOT NULL, FOREIGN KEY',
        $sql
    ) ?? $sql;

    $sql = preg_replace('/foreign key/i', 'FOREIGN KEY', $sql) ?? $sql;
    $sql = preg_replace('/references/i', 'REFERENCES', $sql) ?? $sql;
    $sql = preg_replace('/on delete set null/i', 'ON DELETE SET NULL', $sql) ?? $sql;
    $sql = preg_replace('/on delete cascade/i', 'ON DELETE CASCADE', $sql) ?? $sql;

    $sql = preg_replace('/\)\s*$/', ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci', $sql) ?? $sql;

    return $sql;
}

function sqliteIndexToMysql(string $sql): string
{
    return str_replace('"', '`', $sql);
}

/**
 * @param  list<string>  $columns
 * @param  list<string>  $valueRows
 */
function writeInsert($out, string $table, array $columns, array $valueRows): void
{
    $columnList = implode(', ', array_map(static fn (string $c): string => "`{$c}`", $columns));
    fwrite($out, "INSERT INTO `{$table}` ({$columnList}) VALUES\n");
    fwrite($out, implode(",\n", $valueRows));
    fwrite($out, ";\n");
}

function mysqlValue(string $table, string $column, mixed $value): string
{
    if ($value === null) {
        return 'NULL';
    }

    if (isUnixTimestampColumn($table, $column) && is_numeric($value)) {
        return "'".gmdate('Y-m-d H:i:s', (int) $value)."'";
    }

    if (is_bool($value)) {
        return $value ? '1' : '0';
    }

    if (is_int($value) || is_float($value)) {
        return (string) $value;
    }

    if (is_resource($value)) {
        $contents = stream_get_contents($value);

        return "X'".bin2hex($contents !== false ? $contents : '')."'";
    }

    $string = (string) $value;

    return "'".str_replace(
        ['\\', "\0", "\n", "\r", "'", '"', "\x1a"],
        ['\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'],
        $string
    )."'";
}

/**
 * @return list<string>
 */
function unixTimestampColumns(string $table): array
{
    return match ($table) {
        'cache', 'cache_locks' => ['expiration'],
        'sessions' => ['last_activity'],
        'jobs' => ['reserved_at', 'available_at', 'created_at'],
        'job_batches' => ['cancelled_at', 'created_at', 'finished_at'],
        default => [],
    };
}

function isUnixTimestampColumn(string $table, string $column): bool
{
    return in_array($column, unixTimestampColumns($table), true);
}
