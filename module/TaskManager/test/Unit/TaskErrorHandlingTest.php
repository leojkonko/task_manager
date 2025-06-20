<?php

declare(strict_types=1);

namespace TaskManagerTest\Unit;

use PHPUnit\Framework\TestCase;
use TaskManager\Exception\TaskException;
use TaskManager\Exception\ValidationException;
use TaskManager\Exception\PermissionException;
use TaskManager\Service\TaskService;
use TaskManager\Repository\TaskRepository;
use TaskManager\Validator\TaskBackendValidator;
use TaskManager\Entity\Task;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Testes para cenários de erro e exceções
 * 
 * Foca em:
 * - Tratamento de exceções
 * - Cenários limite (edge cases)
 * - Validação de entrada
 * - Recuperação de erros
 */
class TaskErrorHandlingTest extends TestCase
{
    private TaskService $service;
    private MockObject|TaskRepository $repositoryMock;
    private MockObject|TaskBackendValidator $validatorMock;

    protected function setUp(): void
    {
        $this->repositoryMock = $this->createMock(TaskRepository::class);
        $this->validatorMock = $this->createMock(TaskBackendValidator::class);
        $this->service = new TaskService($this->repositoryMock, $this->validatorMock);
    }

    /**
     * Testa criação com dados completamente vazios
     */
    public function testCreateTaskWithEmptyData(): void
    {
        $this->validatorMock
            ->expects($this->once())
            ->method('validate')
            ->with([])
            ->willReturn([
                'title' => ['Campo obrigatório'],
                'user_id' => ['Campo obrigatório']
            ]);

        $result = $this->service->createTask([]);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('title', $result['errors']);
        $this->assertArrayHasKey('user_id', $result['errors']);
    }

    /**
     * Testa criação com tipos de dados incorretos
     */
    public function testCreateTaskWithWrongDataTypes(): void
    {
        $invalidData = [
            'title' => 123, // Deveria ser string
            'priority' => ['array'], // Deveria ser string
            'user_id' => 'string', // Deveria ser int
            'due_date' => 'invalid-date' // Formato inválido
        ];

        $this->validatorMock
            ->expects($this->once())
            ->method('validate')
            ->with($invalidData)
            ->willReturn([
                'title' => ['Deve ser uma string'],
                'priority' => ['Deve ser uma string válida'],
                'user_id' => ['Deve ser um número inteiro'],
                'due_date' => ['Formato de data inválido']
            ]);

        $result = $this->service->createTask($invalidData);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('errors', $result);
    }

    /**
     * Testa falha na conexão com banco durante criação
     */
    public function testCreateTaskWithDatabaseError(): void
    {
        $validData = [
            'title' => 'Tarefa válida',
            'user_id' => 1
        ];

        $this->validatorMock
            ->expects($this->once())
            ->method('validate')
            ->with($validData)
            ->willReturn([]);

        $this->repositoryMock
            ->expects($this->once())
            ->method('create')
            ->willThrowException(new \PDOException('Connection failed'));

        $this->expectException(\PDOException::class);
        $this->service->createTask($validData);
    }

    /**
     * Testa atualização de tarefa que não existe
     */
    public function testUpdateNonExistentTask(): void
    {
        $nonExistentId = 999;
        $userId = 1;
        $updateData = ['title' => 'Novo título'];

        $this->repositoryMock
            ->expects($this->once())
            ->method('findByIdAndUserId')
            ->with($nonExistentId, $userId)
            ->willReturn(null);

        $result = $this->service->updateTask($nonExistentId, $updateData, $userId);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('não encontrada', $result['message']);
    }

    /**
     * Testa atualização sem permissão (tarefa de outro usuário)
     */
    public function testUpdateTaskWithoutPermission(): void
    {
        $taskId = 1;
        $wrongUserId = 999;
        $updateData = ['title' => 'Hack attempt'];

        $this->repositoryMock
            ->expects($this->once())
            ->method('findByIdAndUserId')
            ->with($taskId, $wrongUserId)
            ->willReturn(null);

        $result = $this->service->updateTask($taskId, $updateData, $wrongUserId);

        $this->assertFalse($result['success']);
    }

    /**
     * Testa exclusão de tarefa já excluída (soft delete)
     */
    public function testDeleteAlreadyDeletedTask(): void
    {
        $taskId = 1;
        $userId = 1;

        $deletedTask = new Task();
        $deletedTask->setId($taskId);
        $deletedTask->setTitle('Tarefa excluída');
        $deletedTask->setUserId($userId);
        $deletedTask->setDeletedAt(new \DateTime());

        $this->repositoryMock
            ->expects($this->once())
            ->method('findByIdAndUserId')
            ->with($taskId, $userId)
            ->willReturn($deletedTask);

        $result = $this->service->deleteTask($taskId, $userId);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('já foi excluída', $result['message']);
    }

    /**
     * Testa busca com termo muito curto
     */
    public function testSearchWithTooShortTerm(): void
    {
        $shortTerm = 'ab'; // Menos de 3 caracteres
        $userId = 1;

        $result = $this->service->searchTasks($shortTerm, $userId);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Testa busca com caracteres especiais perigosos
     */
    public function testSearchWithDangerousCharacters(): void
    {
        $dangerousTerm = "'; DROP TABLE tasks; --";
        $userId = 1;

        $this->repositoryMock
            ->expects($this->once())
            ->method('search')
            ->with($this->stringContains($dangerousTerm), $userId)
            ->willReturn([]);

        $result = $this->service->searchTasks($dangerousTerm, $userId);

        $this->assertIsArray($result);
    }

    /**
     * Testa paginação com valores inválidos
     */
    public function testGetTasksWithInvalidPagination(): void
    {
        $userId = 1;

        // Página negativa
        $result1 = $this->service->getTasks([], -1, 10, $userId);
        $this->assertEquals(1, $result1['pagination']['page']);

        // Página zero
        $result2 = $this->service->getTasks([], 0, 10, $userId);
        $this->assertEquals(1, $result2['pagination']['page']);

        // Limite muito alto
        $result3 = $this->service->getTasks([], 1, 1000, $userId);
        $this->assertLessThanOrEqual(100, $result3['pagination']['limit']);

        // Limite negativo
        $result4 = $this->service->getTasks([], 1, -5, $userId);
        $this->assertGreaterThan(0, $result4['pagination']['limit']);
    }

    /**
     * Testa filtros com valores maliciosos
     */
    public function testGetTasksWithMaliciousFilters(): void
    {
        $userId = 1;
        $maliciousFilters = [
            'status' => "'; DROP TABLE tasks; --",
            'priority' => '<script>alert("xss")</script>',
            'category_id' => 'UNION SELECT * FROM users'
        ];

        $this->repositoryMock
            ->expects($this->once())
            ->method('findByFilters')
            ->with(
                $this->callback(function ($filters) {
                    // Verificar se os filtros foram sanitizados
                    return !str_contains(json_encode($filters), 'DROP TABLE') &&
                           !str_contains(json_encode($filters), '<script>') &&
                           !str_contains(json_encode($filters), 'UNION SELECT');
                }),
                $this->anything(),
                $this->anything(),
                $userId
            )
            ->willReturn(['tasks' => [], 'total' => 0]);

        $result = $this->service->getTasks($maliciousFilters, 1, 10, $userId);

        $this->assertIsArray($result);
    }

    /**
     * Testa timeout de operação
     */
    public function testOperationTimeout(): void
    {
        $taskData = ['title' => 'Tarefa timeout', 'user_id' => 1];

        $this->validatorMock
            ->expects($this->once())
            ->method('validate')
            ->willReturn([]);

        $this->repositoryMock
            ->expects($this->once())
            ->method('create')
            ->willThrowException(new \Exception('Operation timed out'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Operation timed out');

        $this->service->createTask($taskData);
    }

    /**
     * Testa criação com título extremamente longo
     */
    public function testCreateTaskWithVeryLongTitle(): void
    {
        $veryLongTitle = str_repeat('A', 10000); // 10k caracteres
        $taskData = [
            'title' => $veryLongTitle,
            'user_id' => 1
        ];

        $this->validatorMock
            ->expects($this->once())
            ->method('validate')
            ->with($taskData)
            ->willReturn([
                'title' => ['Título deve ter no máximo 255 caracteres']
            ]);

        $result = $this->service->createTask($taskData);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('title', $result['errors']);
    }

    /**
     * Testa criação com caracteres especiais no título
     */
    public function testCreateTaskWithSpecialCharacters(): void
    {
        $specialTitle = "Tarefa com émojis 🚀 e caracteres especiais ñáéíóú @#$%";
        $taskData = [
            'title' => $specialTitle,
            'user_id' => 1
        ];

        $this->validatorMock
            ->expects($this->once())
            ->method('validate')
            ->with($taskData)
            ->willReturn([]);

        $this->repositoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn(1);

        $result = $this->service->createTask($taskData);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['id']);
    }

    /**
     * Testa data de vencimento no passado
     */
    public function testCreateTaskWithPastDueDate(): void
    {
        $pastDate = (new \DateTime())->sub(new \DateInterval('P1D'))->format('Y-m-d');
        $taskData = [
            'title' => 'Tarefa com data passada',
            'due_date' => $pastDate,
            'user_id' => 1
        ];

        $this->validatorMock
            ->expects($this->once())
            ->method('validate')
            ->with($taskData)
            ->willReturn([
                'due_date' => ['Data de vencimento não pode ser no passado']
            ]);

        $result = $this->service->createTask($taskData);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('due_date', $result['errors']);
    }

    /**
     * Testa concorrência - duas atualizações simultâneas
     */
    public function testConcurrentTaskUpdates(): void
    {
        $taskId = 1;
        $userId = 1;

        $existingTask = new Task();
        $existingTask->setId($taskId);
        $existingTask->setTitle('Título original');
        $existingTask->setUserId($userId);
        $existingTask->setUpdatedAt(new \DateTime());

        $this->repositoryMock
            ->expects($this->exactly(2))
            ->method('findByIdAndUserId')
            ->with($taskId, $userId)
            ->willReturn($existingTask);

        $this->validatorMock
            ->expects($this->exactly(2))
            ->method('validate')
            ->willReturn([]);

        $this->repositoryMock
            ->expects($this->exactly(2))
            ->method('update')
            ->willReturn(true);

        // Primeira atualização
        $result1 = $this->service->updateTask($taskId, ['title' => 'Atualização 1'], $userId);
        $this->assertTrue($result1['success']);

        // Segunda atualização
        $result2 = $this->service->updateTask($taskId, ['title' => 'Atualização 2'], $userId);
        $this->assertTrue($result2['success']);
    }

    /**
     * Testa memory leak com muitas operações
     */
    public function testMemoryUsageWithManyOperations(): void
    {
        $initialMemory = memory_get_usage();

        // Simular muitas operações
        for ($i = 0; $i < 1000; $i++) {
            $this->validatorMock
                ->method('validate')
                ->willReturn([]);

            $this->repositoryMock
                ->method('create')
                ->willReturn($i + 1);

            $this->service->createTask([
                'title' => "Task $i",
                'user_id' => 1
            ]);
        }

        $finalMemory = memory_get_usage();
        $memoryIncrease = $finalMemory - $initialMemory;

        // Verificar que o aumento de memória não é excessivo (< 50MB)
        $this->assertLessThan(50 * 1024 * 1024, $memoryIncrease, 
            'Memory increase should be reasonable');
    }

    /**
     * Testa tratamento de caracteres Unicode
     */
    public function testUnicodeCharacterHandling(): void
    {
        $unicodeData = [
            'title' => '测试任务 🌟 العربية русский',
            'description' => 'Descrição com caracteres especiais: ñáéíóú çãô',
            'user_id' => 1
        ];

        $this->validatorMock
            ->expects($this->once())
            ->method('validate')
            ->with($unicodeData)
            ->willReturn([]);

        $this->repositoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn(1);

        $result = $this->service->createTask($unicodeData);

        $this->assertTrue($result['success']);
    }

    /**
     * Testa recuperação após falha de transação
     */
    public function testRecoveryAfterTransactionFailure(): void
    {
        $taskData = ['title' => 'Tarefa transação', 'user_id' => 1];

        $this->validatorMock
            ->method('validate')
            ->willReturn([]);

        // Primeira tentativa falha
        $this->repositoryMock
            ->expects($this->at(0))
            ->method('create')
            ->willThrowException(new \PDOException('Deadlock detected'));

        // Segunda tentativa funciona
        $this->repositoryMock
            ->expects($this->at(1))
            ->method('create')
            ->willReturn(1);

        // Primeira tentativa
        try {
            $this->service->createTask($taskData);
            $this->fail('Should have thrown exception');
        } catch (\PDOException $e) {
            $this->assertStringContainsString('Deadlock', $e->getMessage());
        }

        // Segunda tentativa (retry)
        $result = $this->service->createTask($taskData);
        $this->assertTrue($result['success']);
    }

    /**
     * Testa limite de tentativas de operação
     */
    public function testOperationRetryLimit(): void
    {
        $taskData = ['title' => 'Tarefa retry', 'user_id' => 1];

        $this->validatorMock
            ->method('validate')
            ->willReturn([]);

        $this->repositoryMock
            ->method('create')
            ->willThrowException(new \PDOException('Lock timeout'));

        // Tentar várias vezes até atingir o limite
        $attempts = 0;
        $maxAttempts = 3;

        while ($attempts < $maxAttempts) {
            try {
                $this->service->createTask($taskData);
                break;
            } catch (\PDOException $e) {
                $attempts++;
                if ($attempts >= $maxAttempts) {
                    $this->assertStringContainsString('Lock timeout', $e->getMessage());
                    break;
                }
            }
        }

        $this->assertEquals($maxAttempts, $attempts);
    }
}