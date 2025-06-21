<?php
// Script para debugar problemas de inicialização do módulo Auth

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Debug do Módulo Auth ===\n\n";

// 1. Verificar se o autoload do Composer está funcionando
echo "1. Testando autoload do Auth...\n";
require_once __DIR__ . '/vendor/autoload.php';

try {
    // Tentar carregar a classe principal do módulo
    if (class_exists('Auth\Module')) {
        echo "✓ Classe Auth\Module encontrada\n";
    } else {
        echo "✗ Classe Auth\Module NÃO encontrada\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "✗ Erro ao carregar Auth\Module: " . $e->getMessage() . "\n";
    exit(1);
}

// 2. Verificar se consegue instanciar o módulo
echo "\n2. Testando instanciação do módulo...\n";
try {
    $authModule = new Auth\Module();
    echo "✓ Módulo Auth instanciado com sucesso\n";
} catch (Exception $e) {
    echo "✗ Erro ao instanciar módulo Auth: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

// 3. Verificar se consegue carregar a configuração
echo "\n3. Testando carregamento da configuração...\n";
try {
    $config = $authModule->getConfig();
    echo "✓ Configuração carregada com sucesso\n";
    echo "Rotas encontradas: " . (isset($config['router']['routes']) ? count($config['router']['routes']) : 0) . "\n";
    echo "Controllers encontrados: " . (isset($config['controllers']['factories']) ? count($config['controllers']['factories']) : 0) . "\n";
    echo "Services encontrados: " . (isset($config['service_manager']['factories']) ? count($config['service_manager']['factories']) : 0) . "\n";
} catch (Exception $e) {
    echo "✗ Erro ao carregar configuração: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

// 4. Verificar se as classes dependentes existem
echo "\n4. Testando classes dependentes...\n";
$classes = [
    'Auth\Controller\AuthController',
    'Auth\Service\AuthService',
    'Auth\Service\AuthenticationManager',
    'Auth\Model\User',
    'Auth\Factory\AuthControllerFactory',
    'Auth\Factory\AuthServiceFactory',
    'Auth\Factory\AuthenticationManagerFactory'
];

foreach ($classes as $class) {
    try {
        if (class_exists($class)) {
            echo "✓ $class encontrada\n";
        } else {
            echo "✗ $class NÃO encontrada\n";
        }
    } catch (Exception $e) {
        echo "✗ Erro ao verificar $class: " . $e->getMessage() . "\n";
    }
}

// 5. Tentar simular a inicialização que o Laminas faz
echo "\n5. Testando inicialização completa...\n";
try {
    // Carregar configuração da aplicação
    $appConfig = require __DIR__ . '/config/application.config.php';
    echo "✓ Configuração da aplicação carregada\n";

    // Verificar se Auth está na lista de módulos
    $modules = require __DIR__ . '/config/modules.config.php';
    if (in_array('Auth', $modules)) {
        echo "✓ Módulo Auth está listado em modules.config.php\n";
    } else {
        echo "✗ Módulo Auth NÃO está listado em modules.config.php\n";
    }
} catch (Exception $e) {
    echo "✗ Erro na inicialização: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== Fim do Debug ===\n";
