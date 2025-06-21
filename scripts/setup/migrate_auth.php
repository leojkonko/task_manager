<?php
// Script para executar a migração de autenticação diretamente via PHP

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Migração do Sistema de Autenticação ===\n\n";

require_once __DIR__ . '/vendor/autoload.php';

// Carregar configuração do banco
$config = require __DIR__ . '/config/autoload/database.local.php';
$dbConfig = $config['db'];

try {
    // Conectar ao banco de dados
    $pdo = new PDO(
        $dbConfig['dsn'],
        $dbConfig['username'],
        $dbConfig['password'],
        $dbConfig['driver_options']
    );

    echo "✓ Conexão com banco de dados estabelecida\n\n";

    // Executar migração passo a passo

    // 1. Adicionar colunas à tabela users
    echo "1. Adicionando colunas de autenticação à tabela users...\n";

    $userColumns = [
        "ALTER TABLE users ADD COLUMN email_verified BOOLEAN DEFAULT FALSE",
        "ALTER TABLE users ADD COLUMN last_login TIMESTAMP NULL",
        "ALTER TABLE users ADD COLUMN failed_login_attempts INT DEFAULT 0",
        "ALTER TABLE users ADD COLUMN locked_until TIMESTAMP NULL"
    ];

    foreach ($userColumns as $sql) {
        try {
            $pdo->exec($sql);
            echo "  ✓ " . substr($sql, 0, 50) . "...\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo "  - Coluna já existe: " . substr($sql, 0, 50) . "...\n";
            } else {
                echo "  ✗ Erro: " . $e->getMessage() . "\n";
            }
        }
    }

    // 2. Criar tabela user_sessions
    echo "\n2. Criando tabela user_sessions...\n";
    $sessionsSQL = "
    CREATE TABLE IF NOT EXISTS user_sessions (
        id VARCHAR(128) PRIMARY KEY,
        user_id INT NOT NULL,
        ip_address VARCHAR(45) NOT NULL,
        user_agent TEXT,
        last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expires_at TIMESTAMP NOT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_user_id (user_id),
        INDEX idx_expires_at (expires_at),
        INDEX idx_last_activity (last_activity)
    )";

    try {
        $pdo->exec($sessionsSQL);
        echo "  ✓ Tabela user_sessions criada\n";
    } catch (PDOException $e) {
        echo "  ✗ Erro ao criar user_sessions: " . $e->getMessage() . "\n";
    }

    // 3. Criar tabela password_reset_tokens
    echo "\n3. Criando tabela password_reset_tokens...\n";
    $resetSQL = "
    CREATE TABLE IF NOT EXISTS password_reset_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        token VARCHAR(255) NOT NULL UNIQUE,
        expires_at TIMESTAMP NOT NULL,
        used BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_token (token),
        INDEX idx_expires_at (expires_at)
    )";

    try {
        $pdo->exec($resetSQL);
        echo "  ✓ Tabela password_reset_tokens criada\n";
    } catch (PDOException $e) {
        echo "  ✗ Erro ao criar password_reset_tokens: " . $e->getMessage() . "\n";
    }

    // 4. Criar tabela email_verification_tokens
    echo "\n4. Criando tabela email_verification_tokens...\n";
    $emailSQL = "
    CREATE TABLE IF NOT EXISTS email_verification_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        token VARCHAR(255) NOT NULL UNIQUE,
        expires_at TIMESTAMP NOT NULL,
        used BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_token (token),
        INDEX idx_expires_at (expires_at)
    )";

    try {
        $pdo->exec($emailSQL);
        echo "  ✓ Tabela email_verification_tokens criada\n";
    } catch (PDOException $e) {
        echo "  ✗ Erro ao criar email_verification_tokens: " . $e->getMessage() . "\n";
    }

    // 5. Criar tabela auth_logs
    echo "\n5. Criando tabela auth_logs...\n";
    $logsSQL = "
    CREATE TABLE IF NOT EXISTS auth_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        username VARCHAR(50) NULL,
        action ENUM('login_success', 'login_failed', 'logout', 'password_reset', 'account_locked') NOT NULL,
        ip_address VARCHAR(45) NOT NULL,
        user_agent TEXT,
        details JSON NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_user_id (user_id),
        INDEX idx_action (action),
        INDEX idx_created_at (created_at),
        INDEX idx_ip_address (ip_address)
    )";

    try {
        $pdo->exec($logsSQL);
        echo "  ✓ Tabela auth_logs criada\n";
    } catch (PDOException $e) {
        echo "  ✗ Erro ao criar auth_logs: " . $e->getMessage() . "\n";
    }

    // 6. Atualizar usuários existentes
    echo "\n6. Atualizando usuários existentes...\n";
    try {
        $pdo->exec("UPDATE users SET email_verified = TRUE WHERE username IN ('admin', 'john_doe')");
        echo "  ✓ Usuários existentes atualizados com email_verified = TRUE\n";
    } catch (PDOException $e) {
        echo "  ✗ Erro ao atualizar usuários: " . $e->getMessage() . "\n";
    }

    // 7. Verificar/criar usuário testuser
    echo "\n7. Verificando usuário testuser...\n";
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->execute(['testuser']);

    if ($stmt->fetchColumn() == 0) {
        // Criar usuário testuser
        $passwordHash = password_hash('password123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password_hash, full_name, email_verified, status, created_at, updated_at) 
            VALUES (?, ?, ?, ?, TRUE, 'active', NOW(), NOW())
        ");

        $result = $stmt->execute(['testuser', 'test@taskmanager.com', $passwordHash, 'Test User']);

        if ($result) {
            echo "  ✓ Usuário testuser criado com sucesso\n";
        } else {
            echo "  ✗ Erro ao criar usuário testuser\n";
        }
    } else {
        echo "  - Usuário testuser já existe\n";

        // Atualizar hash da senha do testuser se necessário
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE username = ?");
        $stmt->execute(['testuser']);
        $currentHash = $stmt->fetchColumn();

        if (!password_verify('password123', $currentHash)) {
            echo "  Atualizando senha do testuser...\n";
            $newHash = password_hash('password123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE username = ?");
            $stmt->execute([$newHash, 'testuser']);
            echo "  ✓ Senha do testuser atualizada\n";
        } else {
            echo "  ✓ Senha do testuser já está correta\n";
        }
    }

    echo "\n=== Migração Concluída com Sucesso! ===\n";
    echo "\nAgora você pode fazer login com:\n";
    echo "Username: testuser\n";
    echo "Password: password123\n\n";
} catch (PDOException $e) {
    echo "✗ Erro de conexão com banco: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "✗ Erro geral: " . $e->getMessage() . "\n";
}
