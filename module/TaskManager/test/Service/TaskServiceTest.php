<?php

declare(strict_types=1);

namespace TaskManagerTest\Service;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use TaskManager\Service\TaskService;
use TaskManager\Repository\TaskRepository;
use TaskManager\Entity\Task;
use TaskManager\Validator\TaskBackendValidator;
use DateTime;

/**
 * Testes unitários para TaskService
 * 
 * Testa a lógica de negócio das operações:
 * - Criação de tarefas
 * - Atualização de tarefas
 * - Exclusão de tarefas
 * - Validações de negócio
 * - Regras de autorização
 */
class TaskServiceTest extends TestCase
{
    private TaskService $service;
    private MockObject $repository;
    private MockObject $validator;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(TaskRepository::class);
        $this->validator = $this->createMock(TaskBackendValidator::class);
        $this->service = new TaskService($this->repository, $this->validator);
    }

    /**
     * Testa criação de tarefa válida
     */
    public function testCreateValidTask(): void
    {
        $taskData = [
            'title' => 'Nova tarefa',
            'description' => 'Descrição da tarefa',
            'priority' => 'high',
            'due_date' => '2025-12-31',
            'user_id' => 1
        ];

        $this->validator
            ->expects($this->once())
            ->method('isValid')
            ->with($taskData)
            ->willReturn(true);

        $this->repository
            ->expects($this->once())
            ->method('create')
            ->willReturn(123);

        $result = $this->service->createTask($taskData);

        $this->assertEquals(123, $result['id']);
        $this->assertTrue($result['success']);
        $this->assertEquals('Tarefa criada com sucesso', $result['message']);
    }

    /**
     * Testa criação de tarefa inválida
     */
    public function testCreateInvalidTask(): void
    {
        $taskData = [
            'title' => '', // Título vazio
            'description' => 'Descrição'
        ];

        $errors = ['title' => 'Título é obrigatório'];

        $this->validator
            ->expects($this->once())
            ->method('isValid')
            ->with($taskData)
            ->willReturn(false);

        $this->validator
            ->expects($this->once())
            ->method('getMessages')
            ->willReturn($errors);

        $this->repository
            ->expects($this->never())
            ->method('create');

        $result = $this->service->createTask($taskData);

        $this->assertFalse($result['success']);
        $this->assertEquals('Dados inválidos', $result['message']);
        $this->assertEquals($errors, $result['errors']);
    }

    /**
     * Testa atualização de tarefa existente
     */
    public function testUpdateExistingTask(): void
    {
        $taskId = 1;
        $updateData = [
            'title' => 'Tarefa atualizada',
            'status' => 'in_progress'
        ];

        $existingTask = new Task();
        $existingTask->setId($taskId);
        $existingTask->setTitle('Tarefa original');
        $existingTask->setStatus('pending');
        $existingTask->setUserId(1);

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with($taskId)
            ->willReturn($existingTask);

        $this->validator
            ->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $this->repository
            ->expects($this->once())
            ->method('update')
            ->willReturn(true);

        $result = $this->service->updateTask($taskId, $updateData, 1);

        $this->assertTrue($result['success']);
        $this->assertEquals('Tarefa atualizada com sucesso', $result['message']);
    }

    /**
     * Testa atualização de tarefa inexistente
     */
    public function testUpdateNonExistentTask(): void
    {
        $taskId = 999;
        $updateData = ['title' => 'Nova tarefa'];

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with($taskId)
            ->willReturn(null);

        $result = $this->service->updateTask($taskId, $updateData, 1);

        $this->assertFalse($result['success']);
        $this->assertEquals('Tarefa não encontrada', $result['message']);
    }

    /**
     * Testa atualização sem permissão
     */
    public function testUpdateTaskWithoutPermission(): void
    {
        $taskId = 1;
        $updateData = ['title' => 'Tentativa de atualização'];
        $currentUserId = 2;

        $existingTask = new Task();
        $existingTask->setId($taskId);
        $existingTask->setUserId(1); // Pertence a outro usuário

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with($taskId)
            ->willReturn($existingTask);

        $result = $this->service->updateTask($taskId, $updateData, $currentUserId);

        $this->assertFalse($result['success']);
        $this->assertEquals('Sem permissão para atualizar esta tarefa', $result['message']);
    }

    /**
     * Testa exclusão de tarefa
     */
    public function testDeleteTask(): void
    {
        $taskId = 1;
        $currentUserId = 1;

        $existingTask = new Task();
        $existingTask->setId($taskId);
        $existingTask->setUserId($currentUserId);
        $existingTask->setStatus('pending');
        
        // Define data de criação há mais de 5 dias para permitir exclusão
        $oldDate = new DateTime('-6 days');
        $existingTask->setCreatedAt($oldDate);

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with($taskId)
            ->willReturn($existingTask);

        $this->repository
            ->expects($this->once())
            ->method('delete')
            ->with($taskId)
            ->willReturn(true);

        $result = $this->service->deleteTask($taskId, $currentUserId);

        $this->assertTrue($result['success']);
        $this->assertEquals('Tarefa excluída com sucesso', $result['message']);
    }

    /**
     * Testa exclusão de tarefa que não pode ser excluída
     */
    public function testDeleteTaskNotAllowed(): void
    {
        $taskId = 1;
        $currentUserId = 1;

        $existingTask = new Task();
        $existingTask->setId($taskId);
        $existingTask->setUserId($currentUserId);
        $existingTask->setStatus('completed'); // Status que não permite exclusão

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with($taskId)
            ->willReturn($existingTask);

        $result = $this->service->deleteTask($taskId, $currentUserId);

        $this->assertFalse($result['success']);
        $this->assertEquals('Esta tarefa não pode ser excluída', $result['message']);
    }

    /**
     * Testa marcação como concluída
     */
    public function testCompleteTask(): void
    {
        $taskId = 1;
        $currentUserId = 1;

        $existingTask = new Task();
        $existingTask->setId($taskId);
        $existingTask->setUserId($currentUserId);
        $existingTask->setStatus('in_progress');

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with($taskId)
            ->willReturn($existingTask);

        $this->repository
            ->expects($this->once())
            ->method('markAsCompleted')
            ->with($taskId)
            ->willReturn(true);

        $result = $this->service->completeTask($taskId, $currentUserId);

        $this->assertTrue($result['success']);
        $this->assertEquals('Tarefa marcada como concluída', $result['message']);
    }

    /**
     * Testa marcação como concluída de tarefa já concluída
     */
    public function testCompleteAlreadyCompletedTask(): void
    {
        $taskId = 1;
        $currentUserId = 1;

        $existingTask = new Task();
        $existingTask->setId($taskId);
        $existingTask->setUserId($currentUserId);
        $existingTask->setStatus('completed');

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with($taskId)
            ->willReturn($existingTask);

        $result = $this->service->completeTask($taskId, $currentUserId);

        $this->assertFalse($result['success']);
        $this->assertEquals('Tarefa já está concluída', $result['message']);
    }

    /**
     * Testa duplicação de tarefa
     */
    public function testDuplicateTask(): void
    {
        $taskId = 1;
        $currentUserId = 1;

        $existingTask = new Task();
        $existingTask->setId($taskId);
        $existingTask->setUserId($currentUserId);
        $existingTask->setTitle('Tarefa original');

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with($taskId)
            ->willReturn($existingTask);

        $this->repository
            ->expects($this->once())
            ->method('duplicate')
            ->with($taskId)
            ->willReturn(2);

        $result = $this->service->duplicateTask($taskId, $currentUserId);

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['new_task_id']);
        $this->assertEquals('Tarefa duplicada com sucesso', $result['message']);
    }

    /**
     * Testa busca de tarefas com filtros
     */
    public function testGetTasksWithFilters(): void
    {
        $filters = [
            'status' => 'pending',
            'priority' => 'high'
        ];
        $page = 1;
        $limit = 10;
        $userId = 1;

        $expectedResult = [
            'tasks' => [
                new Task(),
                new Task()
            ],
            'pagination' => [
                'page' => 1,
                'limit' => 10,
                'total' => 2,
                'pages' => 1
            ]
        ];

        $this->repository
            ->expects($this->once())
            ->method('findWithFilters')
            ->with(
                array_merge($filters, ['user_id' => $userId]),
                $page,
                $limit
            )
            ->willReturn($expectedResult);

        $result = $this->service->getTasks($filters, $page, $limit, $userId);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Testa busca de estatísticas
     */
    public function testGetStatistics(): void
    {
        $userId = 1;
        
        $repositoryStats = [
            'pending' => 5,
            'in_progress' => 3,
            'completed' => 12,
            'cancelled' => 1
        ];

        $overdueTasksData = [
            new Task(),
            new Task()
        ];

        $this->repository
            ->expects($this->once())
            ->method('getTaskStatistics')
            ->with(['user_id' => $userId])
            ->willReturn($repositoryStats);

        $this->repository
            ->expects($this->once())
            ->method('findOverdueTasks')
            ->with(['user_id' => $userId])
            ->willReturn($overdueTasksData);

        $result = $this->service->getStatistics($userId);

        $this->assertEquals(21, $result['total_tasks']);
        $this->assertEquals(5, $result['pending']);
        $this->assertEquals(3, $result['in_progress']);
        $this->assertEquals(12, $result['completed']);
        $this->assertEquals(1, $result['cancelled']);
        $this->assertEquals(2, $result['overdue']);
        $this->assertEquals(57.14, $result['completion_rate']); // 12/21 * 100
    }

    /**
     * Testa validação de data de vencimento no passado
     */
    public function testValidatePastDueDate(): void
    {
        $taskData = [
            'title' => 'Tarefa com data passada',
            'due_date' => '2020-01-01', // Data no passado
            'user_id' => 1
        ];

        $this->validator
            ->expects($this->once())
            ->method('isValid')
            ->with($taskData)
            ->willReturn(false);

        $this->validator
            ->expects($this->once())
            ->method('getMessages')
            ->willReturn(['due_date' => 'Data de vencimento não pode ser no passado']);

        $result = $this->service->createTask($taskData);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('due_date', $result['errors']);
    }

    /**
     * Testa busca de tarefas vencidas
     */
    public function testGetOverdueTasks(): void
    {
        $userId = 1;
        
        $overdueTasksData = [
            new Task(),
            new Task(),
            new Task()
        ];

        $this->repository
            ->expects($this->once())
            ->method('findOverdueTasks')
            ->with(['user_id' => $userId])
            ->willReturn($overdueTasksData);

        $result = $this->service->getOverdueTasks($userId);

        $this->assertCount(3, $result);
        $this->assertContainsOnlyInstancesOf(Task::class, $result);
    }

    /**
     * Testa tratamento de exceção no repository
     */
    public function testRepositoryException(): void
    {
        $taskData = [
            'title' => 'Tarefa com erro',
            'user_id' => 1
        ];

        $this->validator
            ->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $this->repository
            ->expects($this->once())
            ->method('create')
            ->willThrowException(new \Exception('Erro de conexão com banco'));

        $result = $this->service->createTask($taskData);

        $this->assertFalse($result['success']);
        $this->assertEquals('Erro interno do servidor', $result['message']);
    }

    /**
     * Testa validação de permissão para visualização
     */
    public function testCanViewTask(): void
    {
        $taskId = 1;
        $userId = 1;

        $task = new Task();
        $task->setId($taskId);
        $task->setUserId($userId);

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with($taskId)
            ->willReturn($task);

        $result = $this->service->canUserAccessTask($taskId, $userId);

        $this->assertTrue($result);
    }

    /**
     * Testa negação de permissão para visualização
     */
    public function testCannotViewTask(): void
    {
        $taskId = 1;
        $userId = 2;

        $task = new Task();
        $task->setId($taskId);
        $task->setUserId(1); // Pertence a outro usuário

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with($taskId)
            ->willReturn($task);

        $result = $this->service->canUserAccessTask($taskId, $userId);

        $this->assertFalse($result);
    }

    /**
     * Testa atualização de prioridade em lote
     */
    public function testBulkUpdatePriority(): void
    {
        $taskIds = [1, 2, 3];
        $newPriority = 'urgent';
        $userId = 1;

        $task1 = new Task();
        $task1->setId(1);
        $task1->setUserId($userId);
        $task1->setStatus('pending');

        $task2 = new Task();
        $task2->setId(2);
        $task2->setUserId($userId);
        $task2->setStatus('in_progress');

        $task3 = new Task();
        $task3->setId(3);
        $task3->setUserId($userId);
        $task3->setStatus('pending');

        $this->repository
            ->expects($this->exactly(3))
            ->method('findById')
            ->willReturnOnConsecutiveCalls($task1, $task2, $task3);

        $this->repository
            ->expects($this->exactly(3))
            ->method('update')
            ->willReturn(true);

        $result = $this->service->bulkUpdatePriority($taskIds, $newPriority, $userId);

        $this->assertTrue($result['success']);
        $this->assertEquals(3, $result['updated_count']);
        $this->assertEquals('3 tarefas atualizadas com sucesso', $result['message']);
    }

    /**
     * Testa exportação de tarefas
     */
    public function testExportTasks(): void
    {
        $userId = 1;
        $filters = ['status' => 'completed'];

        $tasks = [
            new Task(),
            new Task()
        ];

        $this->repository
            ->expects($this->once())
            ->method('findWithFilters')
            ->with(array_merge($filters, ['user_id' => $userId]))
            ->willReturn(['tasks' => $tasks]);

        $result = $this->service->exportTasks($filters, $userId);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
    }
}