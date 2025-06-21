<?php
// Corrigir tabela user_sessions
require_once __DIR__ . '/vendor/autoload.php';
$config = require __DIR__ . '/config/autoload/database.local.php';
$pdo = new PDO($config['db']['dsn'], $config['db']['username'], $config['db']['password'], $config['db']['driver_options']);

$sql = "CREATE TABLE IF NOT EXISTS user_sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

try {
    $pdo->exec($sql);
    echo "✓ Tabela user_sessions criada com sucesso!\n";
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
?>