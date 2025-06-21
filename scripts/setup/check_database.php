<?php
// Script para verificar o estado do banco de dados e usuários

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Verificação do Banco de Dados ===\n\n";

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

    // 1. Verificar se a tabela users existe
    echo "1. Verificando estrutura da tabela users...\n";
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Colunas encontradas:\n";
    foreach ($columns as $column) {
        echo "  - {$column['Field']} ({$column['Type']})\n";
    }

    // Verificar se as colunas de autenticação existem
    $authColumns = ['email_verified', 'last_login', 'failed_login_attempts', 'locked_until'];
    $missingColumns = [];

    $existingColumns = array_column($columns, 'Field');
    foreach ($authColumns as $authCol) {
        if (!in_array($authCol, $existingColumns)) {
            $missingColumns[] = $authCol;
        }
    }

    if (empty($missingColumns)) {
        echo "✓ Todas as colunas de autenticação estão presentes\n\n";
    } else {
        echo "✗ Colunas de autenticação ausentes: " . implode(', ', $missingColumns) . "\n\n";
    }

    // 2. Listar todos os usuários
    echo "2. Listando usuários existentes...\n";
    $stmt = $pdo->query("SELECT id, username, email, full_name, status FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($users)) {
        echo "✗ Nenhum usuário encontrado na tabela\n\n";
    } else {
        echo "Usuários encontrados:\n";
        foreach ($users as $user) {
            echo "  - ID: {$user['id']}, Username: {$user['username']}, Email: {$user['email']}, Status: {$user['status']}\n";
        }
        echo "\n";
    }

    // 3. Verificar especificamente o usuário testuser
    echo "3. Verificando usuário 'testuser'...\n";
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute(['testuser']);
    $testUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($testUser) {
        echo "✓ Usuário 'testuser' encontrado:\n";
        echo "  - ID: {$testUser['id']}\n";
        echo "  - Username: {$testUser['username']}\n";
        echo "  - Email: {$testUser['email']}\n";
        echo "  - Full Name: {$testUser['full_name']}\n";
        echo "  - Status: {$testUser['status']}\n";
        echo "  - Password Hash: " . substr($testUser['password_hash'], 0, 20) . "...\n";

        // Testar a senha
        echo "\n4. Testando verificação de senha...\n";
        $passwordTest = password_verify('password123', $testUser['password_hash']);
        echo $passwordTest ? "✓ Senha 'password123' é válida\n" : "✗ Senha 'password123' é inválida\n";
    } else {
        echo "✗ Usuário 'testuser' não encontrado\n\n";

        // Tentar criar o usuário
        echo "5. Criando usuário 'testuser'...\n";
        $passwordHash = password_hash('password123', PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password_hash, full_name, status, created_at, updated_at) 
            VALUES (?, ?, ?, ?, 'active', NOW(), NOW())
        ");

        $result = $stmt->execute(['testuser', 'test@taskmanager.com', $passwordHash, 'Test User']);

        if ($result) {
            echo "✓ Usuário 'testuser' criado com sucesso\n";
        } else {
            echo "✗ Erro ao criar usuário 'testuser'\n";
        }
    }

    // 4. Verificar outras tabelas de autenticação
    echo "\n6. Verificando tabelas de autenticação...\n";
    $authTables = ['user_sessions', 'password_reset_tokens', 'email_verification_tokens', 'auth_logs'];

    foreach ($authTables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            echo "✓ Tabela '$table' existe com $count registros\n";
        } catch (PDOException $e) {
            echo "✗ Tabela '$table' não existe ou erro: " . $e->getMessage() . "\n";
        }
    }
} catch (PDOException $e) {
    echo "✗ Erro de conexão com banco: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "✗ Erro geral: " . $e->getMessage() . "\n";
}

echo "\n=== Fim da Verificação ===\n";
