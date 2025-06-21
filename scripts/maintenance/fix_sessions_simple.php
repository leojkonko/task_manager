<?php
require_once __DIR__ . '/vendor/autoload.php';
$config = require __DIR__ . '/config/autoload/database.local.php';

$pdo = new PDO($config['db']['dsn'], $config['db']['username'], $config['db']['password'], $config['db']['driver_options']);

// Usar DATETIME em vez de TIMESTAMP para evitar problemas
$pdo->exec("DROP TABLE IF EXISTS user_sessions");
$pdo->exec("CREATE TABLE user_sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    last_activity DATETIME,
    created_at DATETIME,
    expires_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

echo "Tabela user_sessions criada com sucesso!";
