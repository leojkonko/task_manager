<?php
// Script para criar a tabela user_sessions - versão simplificada
require_once __DIR__ . '/vendor/autoload.php';
$config = require __DIR__ . '/config/autoload/database.local.php';

try {
    $pdo = new PDO($config['db']['dsn'], $config['db']['username'], $config['db']['password'], $config['db']['driver_options']);
    
    echo "Criando tabela user_sessions (versão simplificada)...\n";
    
    // Dropar tabela se existir
    $pdo->exec("DROP TABLE IF EXISTS user_sessions");
    
    // Criar tabela com sintaxe bem simples - sem valores padrão problemáticos
    $sql = "CREATE TABLE user_sessions (
        id VARCHAR(128) NOT NULL PRIMARY KEY,
        user_id INT NOT NULL,
        ip_address VARCHAR(45) NOT NULL,
        user_agent TEXT,
        last_activity TIMESTAMP NULL,
        created_at TIMESTAMP NULL,
        expires_at TIMESTAMP NOT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    $pdo->exec($sql);
    echo "✓ Tabela user_sessions criada!\n";
    
    // Testar inserindo um registro de exemplo
    echo "\nTestando inserção na tabela...\n";
    $testId = 'test_session_' . time();
    $stmt = $pdo->prepare("INSERT INTO user_sessions (id, user_id, ip_address, user_agent, created_at, expires_at) VALUES (?, ?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 1 DAY))");
    $result = $stmt->execute([$testId, 4, '127.0.0.1', 'Test Browser']);
    
    if ($result) {
        echo "✓ Teste de inserção bem-sucedido!\n";
        // Limpar o registro de teste
        $pdo->prepare("DELETE FROM user_sessions WHERE id = ?")->execute([$testId]);
        echo "✓ Registro de teste removido\n";
    }
    
    echo "\n🎉 Pronto! Agora você pode fazer login com:\n";
    echo "Username: testuser\n";
    echo "Password: password123\n";
    
} catch (PDOException $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
?>