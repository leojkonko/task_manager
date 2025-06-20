<?php

declare(strict_types=1);

namespace TaskManagerTest\Service;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use TaskManager\Service\TaskService;
use TaskManager\Repository\TaskRepository;
use TaskManager\Entity\Task;
use DateTime;

/**
 * Testes unitários para TaskService
 * 
 * Testa a lógica de negócio:
 * - Validações
 * - Regras de negócio
 * - Interação com repositório
 */
class TaskServiceTest extends TestCase
{
    private TaskService $service;
    private MockObject $repository;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(TaskRepository::class);
        $this->service = new TaskService($this->repository);
    }

    /**
     * Testa criação de tarefa válida
     */
    public function testCreateValidTask(): void
    {
        $taskData = [
            'title' => 'Nova tarefa',
            'description' => 'Descrição da tarefa',
            'due_date' => '2025-12-31',
            'priority' => 'high',
            'status' => 'pending',
            'user_id' => 1  // Adicionando user_id que é obrigatório
        ];

        $expectedTask = new Task();
        $expectedTask->setId(1);
        $expectedTask->setTitle('Nova tarefa');
        $expectedTask->setDescription('Descrição da tarefa');
        $expectedTask->setUserId(1);

        $this->repository
            ->expects($this->once())
            ->method('save')  // Mudando para save que é o método correto
            ->willReturn($expectedTask);

        $result = $this->service->createTask($taskData);

        $this->assertInstanceOf(Task::class, $result);
        $this->assertEquals('Nova tarefa', $result->getTitle());
        $this->assertEquals(1, $result->getId());
    }

    /**
     * Testa busca de tarefa por ID
     */
    public function testGetTaskById(): void
    {
        $task = new Task();
        $task->setId(1);
        $task->setTitle('Tarefa existente');
        $task->setUserId(1);

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($task);

        $result = $this->service->getTask(1);

        $this->assertEquals($task, $result);
    }

    /**
     * Testa busca de tarefa inexistente
     */
    public function testGetTaskNotFound(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $result = $this->service->getTask(999);

        $this->assertNull($result);
    }

    /**
     * Testa atualização de tarefa
     */
    public function testUpdateTask(): void
    {
        $taskData = [
            'title' => 'Tarefa atualizada',
            'description' => 'Nova descrição',
            'status' => 'completed'
        ];

        $existingTask = new Task();
        $existingTask->setId(1);
        $existingTask->setTitle('Tarefa original');
        $existingTask->setUserId(1);

        $updatedTask = clone $existingTask;
        $updatedTask->setTitle('Tarefa atualizada');

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($existingTask);

        $this->repository
            ->expects($this->once())
            ->method('save')  // Mudando para save
            ->willReturn($updatedTask);

        $result = $this->service->updateTask(1, $taskData);

        $this->assertInstanceOf(Task::class, $result); // Esperando Task, não boolean
        $this->assertEquals('Tarefa atualizada', $result->getTitle());
    }

    /**
     * Testa exclusão de tarefa
     */
    public function testDeleteTask(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('delete')
            ->with(1)
            ->willReturn(true);

        $result = $this->service->deleteTask(1);

        $this->assertTrue($result);
    }

    /**
     * Testa listagem de tarefas do usuário
     */
    public function testGetUserTasks(): void
    {
        $tasks = [
            $this->createTaskWithId(1, 'Tarefa 1'),
            $this->createTaskWithId(2, 'Tarefa 2')
        ];

        $this->repository
            ->expects($this->once())
            ->method('findByUserId')
            ->with(1)
            ->willReturn($tasks);

        $result = $this->service->getUserTasks(1);

        $this->assertEquals($tasks, $result);
        $this->assertCount(2, $result);
    }

    /**
     * Testa busca com paginação
     */
    public function testGetTasksWithPagination(): void
    {
        $paginatedResult = [
            'tasks' => [
                $this->createTaskWithId(1, 'Tarefa 1'),
                $this->createTaskWithId(2, 'Tarefa 2')
            ],
            'total' => 5,
            'page' => 1,
            'limit' => 2
        ];

        $this->repository
            ->expects($this->once())
            ->method('findWithPagination')
            ->with(1, 1, 2, [])
            ->willReturn($paginatedResult);

        $result = $this->service->getTasksWithPagination(1, 1, 2);

        $this->assertEquals($paginatedResult, $result);
        $this->assertCount(2, $result['tasks']);
    }

    /**
     * Testa validação de título obrigatório
     */
    public function testValidateRequiredTitle(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('O título da tarefa é obrigatório'); // Mensagem em português

        $taskData = [
            'description' => 'Descrição sem título',
            'priority' => 'medium',
            'user_id' => 1
        ];

        $this->service->createTask($taskData);
    }

    /**
     * Testa validação de prioridade inválida
     */
    public function testValidateInvalidPriority(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Prioridade inválida'); // Mensagem em português

        $taskData = [
            'title' => 'Tarefa com prioridade inválida',
            'priority' => 'invalid_priority',
            'user_id' => 1
        ];

        $this->service->createTask($taskData);
    }

    /**
     * Testa busca de tarefas vencidas
     */
    public function testGetOverdueTasks(): void
    {
        $overdueTasks = [
            $this->createOverdueTask(1, 'Tarefa vencida 1'),
            $this->createOverdueTask(2, 'Tarefa vencida 2')
        ];

        $this->repository
            ->expects($this->once())
            ->method('findOverdueTasks')
            ->with(['user_id' => 1])
            ->willReturn($overdueTasks);

        $result = $this->service->getOverdueTasks(1);

        $this->assertEquals($overdueTasks, $result);
        $this->assertCount(2, $result);
    }

    /**
     * Testa estatísticas de tarefas
     */
    public function testGetTaskStatistics(): void
    {
        $stats = [
            'total' => 10,
            'pending' => 3,
            'in_progress' => 4,
            'completed' => 3
        ];

        $this->repository
            ->expects($this->once())
            ->method('getStatistics')
            ->with(1)
            ->willReturn($stats);

        $result = $this->service->getTaskStatistics(1);

        $this->assertEquals($stats, $result);
        $this->assertEquals(10, $result['total']);
    }

    /**
     * Helper method para criar tarefa com ID
     */
    private function createTaskWithId(int $id, string $title): Task
    {
        $task = new Task();
        $task->setId($id);
        $task->setTitle($title);
        $task->setUserId(1);
        $task->setStatus('pending');
        $task->setPriority('medium');
        return $task;
    }

    /**
     * Helper method para criar tarefa vencida
     */
    private function createOverdueTask(int $id, string $title): Task
    {
        $task = new Task();
        $task->setId($id);
        $task->setTitle($title);
        $task->setUserId(1);
        $task->setStatus('pending');
        $task->setPriority('high');
        $task->setDueDate(new DateTime('2025-01-01')); // Data no passado
        return $task;
    }
}
