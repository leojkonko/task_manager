<?php

declare(strict_types=1);

namespace TaskManagerTest\Repository;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use TaskManager\Repository\TaskRepository;
use TaskManager\Entity\Task;
use TaskManager\Model\TaskTable;
use DateTime;

/**
 * Testes unitários para TaskRepository
 * 
 * Testa todas as operações CRUD:
 * - Create (inserção)
 * - Read (busca, listagem, filtros)
 * - Update (atualização)
 * - Delete (exclusão)
 */
class TaskRepositoryTest extends TestCase
{
    private TaskRepository $repository;
    private MockObject $taskTable;

    protected function setUp(): void
    {
        $this->taskTable = $this->createMock(TaskTable::class);
        $this->repository = new TaskRepository($this->taskTable);
    }

    /**
     * Testa criação de nova tarefa
     */
    public function testCreateTask(): void
    {
        $task = new Task();
        $task->setTitle('Nova tarefa');
        $task->setDescription('Descrição da tarefa');
        $task->setUserId(1);

        $expectedTask = clone $task;
        $expectedTask->setId(1);

        $this->taskTable
            ->expects($this->once())
            ->method('saveTask')
            ->with($task)
            ->willReturn($expectedTask);

        $result = $this->repository->create($task);

        $this->assertEquals($expectedTask, $result);
        $this->assertEquals(1, $result->getId());
        $this->assertEquals('Nova tarefa', $result->getTitle());
    }

    /**
     * Testa busca de tarefa por ID
     */
    public function testFindById(): void
    {
        $task = new Task();
        $task->setId(1);
        $task->setTitle('Tarefa existente');
        $task->setUserId(1);

        $this->taskTable
            ->expects($this->once())
            ->method('getTask')
            ->with(1)
            ->willReturn($task);

        $result = $this->repository->findById(1);

        $this->assertEquals($task, $result);
        $this->assertEquals(1, $result->getId());
        $this->assertEquals('Tarefa existente', $result->getTitle());
    }

    /**
     * Testa busca de tarefa inexistente
     */
    public function testFindByIdNotFound(): void
    {
        $this->taskTable
            ->expects($this->once())
            ->method('getTask')
            ->with(999)
            ->willReturn(null);

        $result = $this->repository->findById(999);

        $this->assertNull($result);
    }

    /**
     * Testa atualização de tarefa
     */
    public function testUpdateTask(): void
    {
        $task = new Task();
        $task->setId(1);
        $task->setTitle('Tarefa atualizada');
        $task->setUserId(1);

        $updatedTask = clone $task;

        $this->taskTable
            ->expects($this->once())
            ->method('saveTask')
            ->with($task)
            ->willReturn($updatedTask);

        $result = $this->repository->update($task);

        $this->assertTrue($result);
    }

    /**
     * Testa exclusão de tarefa
     */
    public function testDeleteTask(): void
    {
        $this->taskTable
            ->expects($this->once())
            ->method('deleteTask')
            ->with(1)
            ->willReturn(true);

        $result = $this->repository->delete(1);

        $this->assertTrue($result);
    }

    /**
     * Testa busca de tarefas por usuário
     */
    public function testFindByUserId(): void
    {
        $tasks = [
            $this->createTaskWithId(1, 'Tarefa 1'),
            $this->createTaskWithId(2, 'Tarefa 2')
        ];

        $this->taskTable
            ->expects($this->once())
            ->method('fetchAll')
            ->with(1)
            ->willReturn($tasks);

        $result = $this->repository->findByUserId(1);

        $this->assertEquals($tasks, $result);
        $this->assertCount(2, $result);
    }

    /**
     * Testa busca com paginação
     */
    public function testFindWithPagination(): void
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

        $this->taskTable
            ->expects($this->once())
            ->method('fetchWithPagination')
            ->with(1, 1, 2, [])
            ->willReturn($paginatedResult);

        $result = $this->repository->findWithPagination(1, 1, 2);

        $this->assertEquals($paginatedResult, $result);
        $this->assertCount(2, $result['tasks']);
        $this->assertEquals(5, $result['total']);
    }

    /**
     * Testa busca de tarefas vencidas
     */
    public function testFindOverdueTasks(): void
    {
        $overdueTasks = [
            $this->createTaskWithId(1, 'Tarefa vencida 1'),
            $this->createTaskWithId(2, 'Tarefa vencida 2')
        ];

        $this->taskTable
            ->expects($this->once())
            ->method('findOverdueTasks')
            ->with(1)
            ->willReturn($overdueTasks);

        $result = $this->repository->findOverdueTasks(['user_id' => 1]);

        $this->assertEquals($overdueTasks, $result);
        $this->assertCount(2, $result);
    }

    /**
     * Testa estatísticas de tarefas
     */
    public function testGetStatistics(): void
    {
        $stats = [
            'total' => 10,
            'pending' => 3,
            'in_progress' => 4,
            'completed' => 3
        ];

        $this->taskTable
            ->expects($this->once())
            ->method('getStatistics')
            ->with(1)
            ->willReturn($stats);

        $result = $this->repository->getStatistics(1);

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
}
