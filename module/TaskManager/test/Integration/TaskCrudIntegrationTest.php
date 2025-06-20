<?php

declare(strict_types=1);

namespace TaskManagerTest\Integration;

use PHPUnit\Framework\TestCase;
use TaskManager\Entity\Task;
use TaskManager\Repository\TaskRepository;
use TaskManager\Service\TaskService;
use TaskManager\Validator\TaskBackendValidator;
use PDO;
use DateTime;

/**
 * Testes de integração para operações CRUD completas
 * 
 * Testa o fluxo completo:
 * - Repository -> Service -> Controller
 * - Validação -> Persistência -> Recuperação
 * - Cenários de erro e sucesso
 * - Performance e concorrência
 */
class TaskCrudIntegrationTest extends TestCase
{
    private PDO $pdo;
    private TaskRepository $repository;
    private TaskService $service;
    private TaskBackendValidator $validator;

    protected function setUp(): void
    {
        // Configurar banco de dados em memória para testes
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Criar estrutura das tabelas
        $this->createTestSchema();
        
        // Inserir dados de teste
        $this->seedTestData();
        
        // Inicializar componentes
        $this->repository = new TaskRepository($this->pdo);
        $this->validator = new TaskBackendValidator();
        $this->service = new TaskService($this->repository, $this->validator);
    }

    protected function tearDown(): void
    {
        $this->pdo = null;
    }

    /**
     * Cria o schema do banco de dados para testes
     */
    private function createTestSchema(): void
    {
        $sql = "
            CREATE TABLE tasks (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                priority VARCHAR(20) DEFAULT 'medium',
                status VARCHAR(20) DEFAULT 'pending',
                due_date DATE,
                user_id INTEGER NOT NULL,
                category_id INTEGER,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                completed_at DATETIME NULL
            );
            
            CREATE TABLE categories (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(100) NOT NULL,
                color VARCHAR(7) DEFAULT '#6c757d',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
            
            CREATE INDEX idx_tasks_user_id ON tasks(user_id);
            CREATE INDEX idx_tasks_status ON tasks(status);
            CREATE INDEX idx_tasks_due_date ON tasks(due_date);
        ";
        
        $this->pdo->exec($sql);
    }

    /**
     * Insere dados de teste
     */
    private function seedTestData(): void
    {
        // Inserir categorias
        $stmt = $this->pdo->prepare("
            INSERT INTO categories (id, name, color) VALUES 
            (1, 'Trabalho', '#007bff'),
            (2, 'Pessoal', '#28a745'),
            (3, 'Estudos', '#ffc107')
        ");
        $stmt->execute();

        // Inserir algumas tarefas de teste
        $stmt = $this->pdo->prepare("
            INSERT INTO tasks (id, title, description, priority, status, user_id, category_id, created_at) VALUES 
            (1, 'Tarefa existente 1', 'Descrição 1', 'high', 'pending', 1, 1, datetime('now', '-2 days')),
            (2, 'Tarefa existente 2', 'Descrição 2', 'medium', 'in_progress', 1, 2, datetime('now', '-1 day')),
            (3, 'Tarefa de outro usuário', 'Descrição 3', 'low', 'pending', 2, 1, datetime('now'))
        ");
        $stmt->execute();
    }

    /**
     * Testa criação completa de tarefa
     */
    public function testCompleteTaskCreation(): void
    {
        $taskData = [
            'title' => 'Nova tarefa de integração',
            'description' => 'Descrição completa da tarefa',
            'priority' => 'urgent',
            'due_date' => '2025-12-31',
            'user_id' => 1,
            'category_id' => 2
        ];

        // Criar tarefa via service
        $result = $this->service->createTask($taskData);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('id', $result);
        $this->assertIsInt($result['id']);

        // Verificar se foi salva no banco
        $savedTask = $this->repository->findById($result['id']);
        $this->assertInstanceOf(Task::class, $savedTask);
        $this->assertEquals($taskData['title'], $savedTask->getTitle());
        $this->assertEquals($taskData['description'], $savedTask->getDescription());
        $this->assertEquals($taskData['priority'], $savedTask->getPriority());
        $this->assertEquals('pending', $savedTask->getStatus()); // Status padrão
        $this->assertEquals($taskData['user_id'], $savedTask->getUserId());
        $this->assertEquals($taskData['category_id'], $savedTask->getCategoryId());
    }

    /**
     * Testa criação com dados inválidos
     */
    public function testCreateTaskWithInvalidData(): void
    {
        $invalidData = [
            'title' => '', // Título vazio
            'priority' => 'invalid_priority',
            'user_id' => -1
        ];

        $result = $this->service->createTask($invalidData);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('title', $result['errors']);
        $this->assertArrayHasKey('priority', $result['errors']);
        $this->assertArrayHasKey('user_id', $result['errors']);
    }

    /**
     * Testa leitura de tarefa existente
     */
    public function testReadExistingTask(): void
    {
        $taskId = 1;
        $userId = 1;

        $task = $this->service->getTaskById($taskId, $userId);

        $this->assertInstanceOf(Task::class, $task);
        $this->assertEquals($taskId, $task->getId());
        $this->assertEquals('Tarefa existente 1', $task->getTitle());
        $this->assertEquals($userId, $task->getUserId());
    }

    /**
     * Testa leitura de tarefa inexistente
     */
    public function testReadNonExistentTask(): void
    {
        $nonExistentId = 999;
        $userId = 1;

        $task = $this->service->getTaskById($nonExistentId, $userId);

        $this->assertNull($task);
    }

    /**
     * Testa leitura de tarefa de outro usuário (sem permissão)
     */
    public function testReadTaskFromAnotherUser(): void
    {
        $taskId = 3; // Tarefa do usuário 2
        $userId = 1; // Tentando acessar como usuário 1

        $task = $this->service->getTaskById($taskId, $userId);

        $this->assertNull($task);
    }

    /**
     * Testa atualização completa de tarefa
     */
    public function testCompleteTaskUpdate(): void
    {
        $taskId = 1;
        $userId = 1;
        $updateData = [
            'title' => 'Título atualizado',
            'description' => 'Nova descrição',
            'priority' => 'low',
            'status' => 'in_progress'
        ];

        $result = $this->service->updateTask($taskId, $updateData, $userId);

        $this->assertTrue($result['success']);

        // Verificar se foi atualizada no banco
        $updatedTask = $this->repository->findById($taskId);
        $this->assertEquals($updateData['title'], $updatedTask->getTitle());
        $this->assertEquals($updateData['description'], $updatedTask->getDescription());
        $this->assertEquals($updateData['priority'], $updatedTask->getPriority());
        $this->assertEquals($updateData['status'], $updatedTask->getStatus());
        $this->assertNotNull($updatedTask->getUpdatedAt());
    }

    /**
     * Testa atualização com dados inválidos
     */
    public function testUpdateTaskWithInvalidData(): void
    {
        $taskId = 1;
        $userId = 1;
        $invalidData = [
            'title' => str_repeat('a', 300), // Título muito longo
            'priority' => 'wrong_priority'
        ];

        $result = $this->service->updateTask($taskId, $invalidData, $userId);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('errors', $result);
    }

    /**
     * Testa atualização de tarefa inexistente
     */
    public function testUpdateNonExistentTask(): void
    {
        $nonExistentId = 999;
        $userId = 1;
        $updateData = ['title' => 'Novo título'];

        $result = $this->service->updateTask($nonExistentId, $updateData, $userId);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('não encontrada', $result['message']);
    }

    /**
     * Testa exclusão de tarefa
     */
    public function testCompleteTaskDeletion(): void
    {
        $taskId = 1;
        $userId = 1;

        // Verificar que existe antes
        $taskBefore = $this->repository->findById($taskId);
        $this->assertInstanceOf(Task::class, $taskBefore);

        $result = $this->service->deleteTask($taskId, $userId);

        $this->assertTrue($result['success']);

        // Verificar que foi removida
        $taskAfter = $this->repository->findById($taskId);
        $this->assertNull($taskAfter);
    }

    /**
     * Testa exclusão de tarefa inexistente
     */
    public function testDeleteNonExistentTask(): void
    {
        $nonExistentId = 999;
        $userId = 1;

        $result = $this->service->deleteTask($nonExistentId, $userId);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('não encontrada', $result['message']);
    }

    /**
     * Testa exclusão sem permissão
     */
    public function testDeleteTaskWithoutPermission(): void
    {
        $taskId = 3; // Tarefa do usuário 2
        $userId = 1; // Tentando excluir como usuário 1

        $result = $this->service->deleteTask($taskId, $userId);

        $this->assertFalse($result['success']);
        
        // Verificar que a tarefa ainda existe
        $task = $this->repository->findById($taskId);
        $this->assertInstanceOf(Task::class, $task);
    }

    /**
     * Testa listagem com filtros
     */
    public function testTaskListingWithFilters(): void
    {
        $userId = 1;
        $filters = [
            'status' => 'pending',
            'priority' => 'high'
        ];

        $result = $this->service->getTasks($filters, 1, 10, $userId);

        $this->assertArrayHasKey('tasks', $result);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertIsArray($result['tasks']);
        
        // Verificar se os filtros foram aplicados
        foreach ($result['tasks'] as $task) {
            $this->assertEquals('pending', $task->getStatus());
            $this->assertEquals('high', $task->getPriority());
            $this->assertEquals($userId, $task->getUserId());
        }
    }

    /**
     * Testa paginação
     */
    public function testPagination(): void
    {
        $userId = 1;
        
        // Criar mais tarefas para testar paginação
        for ($i = 1; $i <= 15; $i++) {
            $this->service->createTask([
                'title' => "Tarefa $i",
                'user_id' => $userId
            ]);
        }

        // Testar primeira página
        $page1 = $this->service->getTasks([], 1, 5, $userId);
        $this->assertCount(5, $page1['tasks']);
        $this->assertEquals(1, $page1['pagination']['page']);

        // Testar segunda página
        $page2 = $this->service->getTasks([], 2, 5, $userId);
        $this->assertCount(5, $page2['tasks']);
        $this->assertEquals(2, $page2['pagination']['page']);

        // Verificar que são tarefas diferentes
        $page1Ids = array_map(fn($task) => $task->getId(), $page1['tasks']);
        $page2Ids = array_map(fn($task) => $task->getId(), $page2['tasks']);
        $this->assertEmpty(array_intersect($page1Ids, $page2Ids));
    }

    /**
     * Testa busca por texto
     */
    public function testTextSearch(): void
    {
        $userId = 1;
        $searchTerm = 'existente';

        $results = $this->service->searchTasks($searchTerm, $userId);

        $this->assertIsArray($results);
        $this->assertNotEmpty($results);
        
        foreach ($results as $result) {
            $hasMatchInTitle = stripos($result['title'], $searchTerm) !== false;
            $hasMatchInDescription = stripos($result['description'] ?? '', $searchTerm) !== false;
            $this->assertTrue($hasMatchInTitle || $hasMatchInDescription);
        }
    }

    /**
     * Testa conclusão de tarefa
     */
    public function testCompleteTask(): void
    {
        $taskId = 1;
        $userId = 1;

        $result = $this->service->completeTask($taskId, $userId);

        $this->assertTrue($result['success']);

        // Verificar se foi marcada como completa
        $completedTask = $this->repository->findById($taskId);
        $this->assertEquals('completed', $completedTask->getStatus());
        $this->assertNotNull($completedTask->getCompletedAt());
    }

    /**
     * Testa duplicação de tarefa
     */
    public function testDuplicateTask(): void
    {
        $originalId = 1;
        $userId = 1;

        $result = $this->service->duplicateTask($originalId, $userId);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('new_task_id', $result);

        // Verificar tarefa original
        $original = $this->repository->findById($originalId);
        
        // Verificar tarefa duplicada
        $duplicate = $this->repository->findById($result['new_task_id']);
        
        $this->assertNotEquals($original->getId(), $duplicate->getId());
        $this->assertStringContainsString('Cópia de', $duplicate->getTitle());
        $this->assertEquals($original->getDescription(), $duplicate->getDescription());
        $this->assertEquals($original->getPriority(), $duplicate->getPriority());
        $this->assertEquals('pending', $duplicate->getStatus()); // Status resetado
        $this->assertEquals($original->getUserId(), $duplicate->getUserId());
    }

    /**
     * Testa transação ao criar tarefa com erro
     */
    public function testTransactionRollbackOnError(): void
    {
        // Simular erro durante a criação
        $invalidData = [
            'title' => null, // Vai causar erro no banco
            'user_id' => 1
        ];

        $taskCountBefore = $this->repository->countByUserId(1);

        try {
            $this->service->createTask($invalidData);
        } catch (\Exception $e) {
            // Esperado que lance exceção
        }

        $taskCountAfter = $this->repository->countByUserId(1);
        
        // Verificar que nenhuma tarefa foi criada
        $this->assertEquals($taskCountBefore, $taskCountAfter);
    }

    /**
     * Testa concorrência na atualização de tarefas
     */
    public function testConcurrentUpdates(): void
    {
        $taskId = 1;
        $userId = 1;

        // Simular duas atualizações simultâneas
        $update1 = ['title' => 'Atualização 1'];
        $update2 = ['title' => 'Atualização 2'];

        $this->service->updateTask($taskId, $update1, $userId);
        $this->service->updateTask($taskId, $update2, $userId);

        // A última atualização deve prevalecer
        $finalTask = $this->repository->findById($taskId);
        $this->assertEquals('Atualização 2', $finalTask->getTitle());
    }

    /**
     * Testa performance com grande volume de dados
     */
    public function testPerformanceWithLargeDataset(): void
    {
        $userId = 1;
        
        // Criar 100 tarefas
        $startTime = microtime(true);
        
        for ($i = 1; $i <= 100; $i++) {
            $this->service->createTask([
                'title' => "Performance Task $i",
                'description' => "Description for task $i",
                'priority' => ['low', 'medium', 'high', 'urgent'][rand(0, 3)],
                'user_id' => $userId,
                'category_id' => rand(1, 3)
            ]);
        }
        
        $createTime = microtime(true) - $startTime;
        
        // Testar listagem
        $startTime = microtime(true);
        $result = $this->service->getTasks([], 1, 50, $userId);
        $listTime = microtime(true) - $startTime;
        
        // Testar busca
        $startTime = microtime(true);
        $searchResults = $this->service->searchTasks('Task', $userId);
        $searchTime = microtime(true) - $startTime;

        // Verificar performance aceitável
        $this->assertLessThan(5.0, $createTime, 'Criação de 100 tarefas deve ser rápida');
        $this->assertLessThan(0.5, $listTime, 'Listagem deve ser rápida');
        $this->assertLessThan(1.0, $searchTime, 'Busca deve ser rápida');
        
        $this->assertCount(50, $result['tasks']);
        $this->assertGreaterThan(50, count($searchResults));
    }

    /**
     * Testa integridade referencial
     */
    public function testReferentialIntegrity(): void
    {
        // Criar tarefa com categoria
        $taskData = [
            'title' => 'Tarefa com categoria',
            'user_id' => 1,
            'category_id' => 1
        ];

        $result = $this->service->createTask($taskData);
        $this->assertTrue($result['success']);

        // Verificar se a categoria existe na tarefa criada
        $task = $this->repository->findById($result['id']);
        $this->assertEquals(1, $task->getCategoryId());
    }

    /**
     * Testa validação de regras de negócio complexas
     */
    public function testComplexBusinessRules(): void
    {
        $userId = 1;

        // Testa criação durante fim de semana (se implementado)
        $weekendData = [
            'title' => 'Tarefa de fim de semana',
            'user_id' => $userId
        ];
        
        // Mock da data para sábado
        $saturday = new DateTime('2025-06-21');
        $this->validator->setCurrentDate($saturday);
        
        $result = $this->service->createTask($weekendData);
        // Depende da implementação da regra de negócio
        
        // Testa limite diário de tarefas (se implementado)
        for ($i = 1; $i <= 11; $i++) {
            $result = $this->service->createTask([
                'title' => "Tarefa limite $i",
                'user_id' => $userId
            ]);
            
            if ($i <= 10) {
                $this->assertTrue($result['success'], "Tarefa $i deveria ser criada");
            } else {
                // A 11ª tarefa pode ser rejeitada se há limite de 10 por dia
                // Depende da implementação
            }
        }
    }

    /**
     * Testa recuperação de erros
     */
    public function testErrorRecovery(): void
    {
        // Simular falha de conexão temporária
        // Em um cenário real, isso testaria reconexão automática
        
        $taskData = [
            'title' => 'Tarefa após erro',
            'user_id' => 1
        ];

        $result = $this->service->createTask($taskData);
        $this->assertTrue($result['success']);
    }
}