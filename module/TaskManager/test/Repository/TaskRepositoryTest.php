<?php

declare(strict_types=1);

namespace TaskManagerTest\Repository;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use TaskManager\Repository\TaskRepository;
use TaskManager\Entity\Task;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\Db\Sql\Select;
use Laminas\Db\ResultSet\ResultSet;
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
    private MockObject $tableGateway;
    private MockObject $adapter;

    protected function setUp(): void
    {
        $this->adapter = $this->createMock(Adapter::class);
        $this->tableGateway = $this->createMock(TableGateway::class);
        $this->repository = new TaskRepository($this->tableGateway);
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

        $this->tableGateway
            ->expects($this->once())
            ->method('insert')
            ->with($this->callback(function ($data) {
                return $data['title'] === 'Nova tarefa' &&
                       $data['description'] === 'Descrição da tarefa' &&
                       $data['user_id'] === 1 &&
                       $data['status'] === 'pending' &&
                       $data['priority'] === 'medium';
            }))
            ->willReturn(1);

        $this->tableGateway
            ->expects($this->once())
            ->method('getLastInsertValue')
            ->willReturn('123');

        $result = $this->repository->create($task);

        $this->assertEquals(123, $result);
    }

    /**
     * Testa busca de tarefa por ID
     */
    public function testFindById(): void
    {
        $taskData = [
            'id' => 1,
            'title' => 'Tarefa encontrada',
            'description' => 'Descrição',
            'status' => 'pending',
            'priority' => 'high',
            'user_id' => 1,
            'created_at' => '2025-06-19 10:00:00',
            'updated_at' => '2025-06-19 10:00:00'
        ];

        $resultSet = $this->createMock(ResultSet::class);
        $resultSet->expects($this->once())
            ->method('current')
            ->willReturn($taskData);

        $this->tableGateway
            ->expects($this->once())
            ->method('select')
            ->with(['id' => 1])
            ->willReturn($resultSet);

        $task = $this->repository->findById(1);

        $this->assertInstanceOf(Task::class, $task);
        $this->assertEquals(1, $task->getId());
        $this->assertEquals('Tarefa encontrada', $task->getTitle());
        $this->assertEquals('pending', $task->getStatus());
    }

    /**
     * Testa busca de tarefa inexistente
     */
    public function testFindByIdNotFound(): void
    {
        $resultSet = $this->createMock(ResultSet::class);
        $resultSet->expects($this->once())
            ->method('current')
            ->willReturn(null);

        $this->tableGateway
            ->expects($this->once())
            ->method('select')
            ->with(['id' => 999])
            ->willReturn($resultSet);

        $task = $this->repository->findById(999);

        $this->assertNull($task);
    }

    /**
     * Testa listagem de todas as tarefas
     */
    public function testFindAll(): void
    {
        $tasksData = [
            [
                'id' => 1,
                'title' => 'Tarefa 1',
                'status' => 'pending',
                'priority' => 'medium',
                'user_id' => 1,
                'created_at' => '2025-06-19 10:00:00',
                'updated_at' => '2025-06-19 10:00:00'
            ],
            [
                'id' => 2,
                'title' => 'Tarefa 2',
                'status' => 'completed',
                'priority' => 'high',
                'user_id' => 1,
                'created_at' => '2025-06-19 11:00:00',
                'updated_at' => '2025-06-19 11:00:00'
            ]
        ];

        $resultSet = $this->createMock(ResultSet::class);
        $resultSet->expects($this->once())
            ->method('toArray')
            ->willReturn($tasksData);

        $this->tableGateway
            ->expects($this->once())
            ->method('select')
            ->willReturn($resultSet);

        $tasks = $this->repository->findAll();

        $this->assertIsArray($tasks);
        $this->assertCount(2, $tasks);
        $this->assertInstanceOf(Task::class, $tasks[0]);
        $this->assertInstanceOf(Task::class, $tasks[1]);
        $this->assertEquals('Tarefa 1', $tasks[0]->getTitle());
        $this->assertEquals('Tarefa 2', $tasks[1]->getTitle());
    }

    /**
     * Testa busca com filtros
     */
    public function testFindWithFilters(): void
    {
        $filters = [
            'status' => 'pending',
            'priority' => 'high',
            'search' => 'importante'
        ];

        $this->tableGateway
            ->expects($this->once())
            ->method('select')
            ->with($this->callback(function ($select) {
                // Verifica se é um objeto Select ou array de condições
                return is_array($select) || $select instanceof Select;
            }));

        $this->repository->findWithFilters($filters);
    }

    /**
     * Testa busca com paginação
     */
    public function testFindWithPagination(): void
    {
        $filters = [];
        $page = 2;
        $limit = 10;

        $tasksData = array_fill(0, 10, [
            'id' => 1,
            'title' => 'Tarefa paginada',
            'status' => 'pending',
            'priority' => 'medium',
            'user_id' => 1,
            'created_at' => '2025-06-19 10:00:00',
            'updated_at' => '2025-06-19 10:00:00'
        ]);

        $resultSet = $this->createMock(ResultSet::class);
        $resultSet->expects($this->once())
            ->method('toArray')
            ->willReturn($tasksData);

        $this->tableGateway
            ->expects($this->once())
            ->method('select')
            ->willReturn($resultSet);

        $result = $this->repository->findWithFilters($filters, $page, $limit);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('tasks', $result);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertEquals($page, $result['pagination']['page']);
        $this->assertEquals($limit, $result['pagination']['limit']);
    }

    /**
     * Testa contagem de tarefas
     */
    public function testCountTasks(): void
    {
        $resultSet = $this->createMock(ResultSet::class);
        $resultSet->expects($this->once())
            ->method('current')
            ->willReturn(['total' => 25]);

        $this->tableGateway
            ->expects($this->once())
            ->method('select')
            ->willReturn($resultSet);

        $count = $this->repository->countTasks();

        $this->assertEquals(25, $count);
    }

    /**
     * Testa contagem com filtros
     */
    public function testCountTasksWithFilters(): void
    {
        $filters = ['status' => 'pending'];

        $resultSet = $this->createMock(ResultSet::class);
        $resultSet->expects($this->once())
            ->method('current')
            ->willReturn(['total' => 15]);

        $this->tableGateway
            ->expects($this->once())
            ->method('select')
            ->willReturn($resultSet);

        $count = $this->repository->countTasks($filters);

        $this->assertEquals(15, $count);
    }

    /**
     * Testa atualização de tarefa
     */
    public function testUpdateTask(): void
    {
        $task = new Task();
        $task->setId(1);
        $task->setTitle('Tarefa atualizada');
        $task->setDescription('Nova descrição');
        $task->setStatus('in_progress');

        $this->tableGateway
            ->expects($this->once())
            ->method('update')
            ->with(
                $this->callback(function ($data) {
                    return $data['title'] === 'Tarefa atualizada' &&
                           $data['description'] === 'Nova descrição' &&
                           $data['status'] === 'in_progress';
                }),
                ['id' => 1]
            )
            ->willReturn(1);

        $result = $this->repository->update($task);

        $this->assertTrue($result);
    }

    /**
     * Testa atualização de tarefa inexistente
     */
    public function testUpdateNonExistentTask(): void
    {
        $task = new Task();
        $task->setId(999);
        $task->setTitle('Tarefa inexistente');

        $this->tableGateway
            ->expects($this->once())
            ->method('update')
            ->willReturn(0); // Nenhuma linha afetada

        $result = $this->repository->update($task);

        $this->assertFalse($result);
    }

    /**
     * Testa exclusão de tarefa
     */
    public function testDeleteTask(): void
    {
        $this->tableGateway
            ->expects($this->once())
            ->method('delete')
            ->with(['id' => 1])
            ->willReturn(1);

        $result = $this->repository->delete(1);

        $this->assertTrue($result);
    }

    /**
     * Testa exclusão de tarefa inexistente
     */
    public function testDeleteNonExistentTask(): void
    {
        $this->tableGateway
            ->expects($this->once())
            ->method('delete')
            ->with(['id' => 999])
            ->willReturn(0); // Nenhuma linha afetada

        $result = $this->repository->delete(999);

        $this->assertFalse($result);
    }

    /**
     * Testa marcação de tarefa como concluída
     */
    public function testMarkAsCompleted(): void
    {
        $this->tableGateway
            ->expects($this->once())
            ->method('update')
            ->with(
                $this->callback(function ($data) {
                    return $data['status'] === 'completed' &&
                           isset($data['updated_at']);
                }),
                ['id' => 1]
            )
            ->willReturn(1);

        $result = $this->repository->markAsCompleted(1);

        $this->assertTrue($result);
    }

    /**
     * Testa duplicação de tarefa
     */
    public function testDuplicateTask(): void
    {
        // Mock da tarefa original
        $originalTaskData = [
            'id' => 1,
            'title' => 'Tarefa original',
            'description' => 'Descrição original',
            'status' => 'completed',
            'priority' => 'high',
            'user_id' => 1,
            'category_id' => 2,
            'created_at' => '2025-06-19 10:00:00',
            'updated_at' => '2025-06-19 10:00:00'
        ];

        $resultSet = $this->createMock(ResultSet::class);
        $resultSet->expects($this->once())
            ->method('current')
            ->willReturn($originalTaskData);

        // Mock para buscar a tarefa original
        $this->tableGateway
            ->expects($this->at(0))
            ->method('select')
            ->with(['id' => 1])
            ->willReturn($resultSet);

        // Mock para inserir a nova tarefa
        $this->tableGateway
            ->expects($this->once())
            ->method('insert')
            ->with($this->callback(function ($data) {
                return $data['title'] === '[CÓPIA] Tarefa original' &&
                       $data['description'] === 'Descrição original' &&
                       $data['status'] === 'pending' &&
                       $data['priority'] === 'high' &&
                       $data['user_id'] === 1 &&
                       $data['category_id'] === 2;
            }))
            ->willReturn(1);

        $this->tableGateway
            ->expects($this->once())
            ->method('getLastInsertValue')
            ->willReturn('2');

        $newTaskId = $this->repository->duplicate(1);

        $this->assertEquals(2, $newTaskId);
    }

    /**
     * Testa duplicação de tarefa inexistente
     */
    public function testDuplicateNonExistentTask(): void
    {
        $resultSet = $this->createMock(ResultSet::class);
        $resultSet->expects($this->once())
            ->method('current')
            ->willReturn(null);

        $this->tableGateway
            ->expects($this->once())
            ->method('select')
            ->with(['id' => 999])
            ->willReturn($resultSet);

        $result = $this->repository->duplicate(999);

        $this->assertNull($result);
    }

    /**
     * Testa busca de tarefas vencidas
     */
    public function testFindOverdueTasks(): void
    {
        $overdueTasksData = [
            [
                'id' => 1,
                'title' => 'Tarefa vencida',
                'status' => 'pending',
                'priority' => 'urgent',
                'due_date' => '2025-06-18 23:59:59',
                'user_id' => 1,
                'created_at' => '2025-06-15 10:00:00',
                'updated_at' => '2025-06-15 10:00:00'
            ]
        ];

        $resultSet = $this->createMock(ResultSet::class);
        $resultSet->expects($this->once())
            ->method('toArray')
            ->willReturn($overdueTasksData);

        $this->tableGateway
            ->expects($this->once())
            ->method('select')
            ->willReturn($resultSet);

        $tasks = $this->repository->findOverdueTasks();

        $this->assertIsArray($tasks);
        $this->assertCount(1, $tasks);
        $this->assertInstanceOf(Task::class, $tasks[0]);
        $this->assertEquals('Tarefa vencida', $tasks[0]->getTitle());
    }

    /**
     * Testa busca de tarefas por usuário
     */
    public function testFindByUserId(): void
    {
        $userTasksData = [
            [
                'id' => 1,
                'title' => 'Tarefa do usuário',
                'status' => 'pending',
                'priority' => 'medium',
                'user_id' => 123,
                'created_at' => '2025-06-19 10:00:00',
                'updated_at' => '2025-06-19 10:00:00'
            ]
        ];

        $resultSet = $this->createMock(ResultSet::class);
        $resultSet->expects($this->once())
            ->method('toArray')
            ->willReturn($userTasksData);

        $this->tableGateway
            ->expects($this->once())
            ->method('select')
            ->with(['user_id' => 123])
            ->willReturn($resultSet);

        $tasks = $this->repository->findByUserId(123);

        $this->assertIsArray($tasks);
        $this->assertCount(1, $tasks);
        $this->assertInstanceOf(Task::class, $tasks[0]);
        $this->assertEquals(123, $tasks[0]->getUserId());
    }

    /**
     * Testa estatísticas de tarefas
     */
    public function testGetTaskStatistics(): void
    {
        $statsData = [
            ['status' => 'pending', 'count' => 10],
            ['status' => 'completed', 'count' => 25],
            ['status' => 'in_progress', 'count' => 5],
            ['status' => 'cancelled', 'count' => 2]
        ];

        $resultSet = $this->createMock(ResultSet::class);
        $resultSet->expects($this->once())
            ->method('toArray')
            ->willReturn($statsData);

        $this->tableGateway
            ->expects($this->once())
            ->method('select')
            ->willReturn($resultSet);

        $stats = $this->repository->getTaskStatistics();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('pending', $stats);
        $this->assertArrayHasKey('completed', $stats);
        $this->assertArrayHasKey('in_progress', $stats);
        $this->assertArrayHasKey('cancelled', $stats);
        $this->assertEquals(10, $stats['pending']);
        $this->assertEquals(25, $stats['completed']);
    }

    /**
     * Testa tratamento de exceção na criação
     */
    public function testCreateTaskWithException(): void
    {
        $task = new Task();
        $task->setTitle('Tarefa com erro');

        $this->tableGateway
            ->expects($this->once())
            ->method('insert')
            ->willThrowException(new \Exception('Erro de banco de dados'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Erro de banco de dados');

        $this->repository->create($task);
    }

    /**
     * Testa validação de dados antes da inserção
     */
    public function testCreateTaskValidation(): void
    {
        $task = new Task();
        // Não define título (obrigatório)

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Título é obrigatório');

        $this->repository->create($task);
    }
}