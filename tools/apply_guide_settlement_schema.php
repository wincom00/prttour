<?php
require_once __DIR__ . '/../include/dbconn.php';

function column_exists($table, $column)
{
    $table = mysql_real_escape_string($table);
    $column = mysql_real_escape_string($column);
    $res = mysql_query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
    return $res && mysql_num_rows($res) > 0;
}

function run_sql($sql)
{
    if (!mysql_query($sql)) {
        fwrite(STDERR, mysql_error() . PHP_EOL . $sql . PHP_EOL);
        exit(1);
    }
}

if (!column_exists('guide_setmaster', 'guide_memo')) {
    run_sql("ALTER TABLE guide_setmaster ADD COLUMN guide_memo TEXT NULL AFTER wdate");
    echo "ADDED guide_memo" . PHP_EOL;
} else {
    echo "EXISTS guide_memo" . PHP_EOL;
}

if (column_exists('guide_setmaster', 'g_memo')) {
    run_sql("UPDATE guide_setmaster
                SET guide_memo = g_memo
              WHERE (guide_memo IS NULL OR guide_memo = '')
                AND g_memo IS NOT NULL
                AND g_memo <> ''");
    echo "COPIED " . mysql_affected_rows() . " rows" . PHP_EOL;
}

run_sql("CREATE TABLE IF NOT EXISTS guide_set_check (
    id INT NOT NULL AUTO_INCREMENT,
    settle_code VARCHAR(100) NOT NULL,
    check_no VARCHAR(100) NOT NULL DEFAULT '',
    bank_name VARCHAR(100) DEFAULT NULL,
    used_date DATE DEFAULT NULL,
    amount DECIMAL(12,2) DEFAULT 0.00,
    note VARCHAR(255) DEFAULT NULL,
    reg_user VARCHAR(20) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_settle_code (settle_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo "OK guide_set_check" . PHP_EOL;
