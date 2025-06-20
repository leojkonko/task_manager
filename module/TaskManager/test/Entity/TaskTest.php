<?php

declare(strict_types=1);

namespace TaskManagerTest\Entity;

use PHPUnit\Framework\TestCase;
use TaskManager\Entity\Task;
use DateTime;

/**
 * Testes unitários para a entidade Task
 * 
 * Testa todas as funcionalidades da entidade Task:
 * - Criação e inicialização
 * - Getters e setters
 * - Métodos de estado
 * - Validações de estado
 */
class TaskTest extends TestCase
{
    private Task $task;

    protected function setUp(): void
    {
        $this->task = new Task();
    }

    /**
     * Testa criação e inicialização da entidade
     */
    public function testTaskCreation(): void
    {
        $task = new Task();

        $this->assertInstanceOf(Task::class, $task);
        $this->assertNull($task->getId());
        $this->assertEquals('pending', $task->getStatus());
        $this->assertEquals('medium', $task->getPriority());
        $this->assertInstanceOf(DateTime::class, $task->getCreatedAt());
        $this->assertInstanceOf(DateTime::class, $task->getUpdatedAt());
        $this->assertNull($task->getDescription());
        $this->assertNull($task->getDueDate());
        $this->assertNull($task->getCompletedAt());
        $this->assertNull($task->getCategoryId());
    }

    /**
     * Testa setter e getter do ID
     */
    public function testIdSetterGetter(): void
    {
        $this->task->setId(123);
        $this->assertEquals(123, $this->task->getId());

        // Teste com null
        $this->task->setId(null);
        $this->assertNull($this->task->getId());
    }

    /**
     * Testa setter e getter do título
     */
    public function testTitleSetterGetter(): void
    {
        $title = 'Minha tarefa de teste';
        $this->task->setTitle($title);
        $this->assertEquals($title, $this->task->getTitle());
    }

    /**
     * Testa validação do título
     */
    public function testTitleValidation(): void
    {
        // Título muito curto
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('O título deve ter pelo menos 3 caracteres');
        $this->task->setTitle('ab');
    }

    public function testTitleTooLong(): void
    {
        // Título muito longo (mais de 200 caracteres)
        $longTitle = str_repeat('a', 201);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('O título não pode ter mais de 200 caracteres');
        $this->task->setTitle($longTitle);
    }

    public function testEmptyTitle(): void
    {
        // Título vazio
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('O título da tarefa não pode estar vazio');
        $this->task->setTitle('   ');
    }

    /**
     * Testa setter e getter da descrição
     */
    public function testDescriptionSetterGetter(): void
    {
        $description = 'Esta é uma descrição detalhada da tarefa';
        $this->task->setDescription($description);
        $this->assertEquals($description, $this->task->getDescription());

        // Teste com null
        $this->task->setDescription(null);
        $this->assertNull($this->task->getDescription());

        // Teste com string vazia (deve virar null)
        $this->task->setDescription('   ');
        $this->assertNull($this->task->getDescription());
    }

    public function testDescriptionTooLong(): void
    {
        // Descrição muito longa (mais de 1000 caracteres)
        $longDescription = str_repeat('a', 1001);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A descrição não pode ter mais de 1000 caracteres');
        $this->task->setDescription($longDescription);
    }

    /**
     * Testa setter e getter do status
     */
    public function testStatusSetterGetter(): void
    {
        $validStatuses = ['pending', 'in_progress', 'completed', 'cancelled'];

        foreach ($validStatuses as $status) {
            $this->task->setStatus($status);
            $this->assertEquals($status, $this->task->getStatus());
        }
    }

    public function testInvalidStatus(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Status inválido: invalid_status');
        $this->task->setStatus('invalid_status');
    }

    /**
     * Testa setter e getter da prioridade
     */
    public function testPrioritySetterGetter(): void
    {
        $validPriorities = ['low', 'medium', 'high', 'urgent'];

        foreach ($validPriorities as $priority) {
            $this->task->setPriority($priority);
            $this->assertEquals($priority, $this->task->getPriority());
        }
    }

    public function testInvalidPriority(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Prioridade inválida: invalid_priority');
        $this->task->setPriority('invalid_priority');
    }

    /**
     * Testa setter e getter da data de vencimento
     */
    public function testDueDateSetterGetter(): void
    {
        $dueDate = new DateTime('+7 days');
        $this->task->setDueDate($dueDate);
        $this->assertEquals($dueDate, $this->task->getDueDate());

        // Teste com null
        $this->task->setDueDate(null);
        $this->assertNull($this->task->getDueDate());
    }

    /**
     * Testa setter e getter da data de conclusão
     */
    public function testCompletedAtSetterGetter(): void
    {
        $completedAt = new DateTime();
        $this->task->setCompletedAt($completedAt);
        $this->assertEquals($completedAt, $this->task->getCompletedAt());

        // Teste com null
        $this->task->setCompletedAt(null);
        $this->assertNull($this->task->getCompletedAt());
    }

    /**
     * Testa setter e getter do user_id
     */
    public function testUserIdSetterGetter(): void
    {
        $userId = 42;
        $this->task->setUserId($userId);
        $this->assertEquals($userId, $this->task->getUserId());
    }

    /**
     * Testa setter e getter do category_id
     */
    public function testCategoryIdSetterGetter(): void
    {
        $categoryId = 15;
        $this->task->setCategoryId($categoryId);
        $this->assertEquals($categoryId, $this->task->getCategoryId());

        // Teste com null
        $this->task->setCategoryId(null);
        $this->assertNull($this->task->getCategoryId());
    }

    /**
     * Testa setter e getter das datas
     */
    public function testDateSettersGetters(): void
    {
        $createdAt = new DateTime('2025-01-01 10:00:00');
        $updatedAt = new DateTime('2025-01-02 15:30:00');

        $this->task->setCreatedAt($createdAt);
        $this->task->setUpdatedAt($updatedAt);

        $this->assertEquals($createdAt, $this->task->getCreatedAt());
        $this->assertEquals($updatedAt, $this->task->getUpdatedAt());
    }

    /**
     * Testa método isCompleted
     */
    public function testIsCompleted(): void
    {
        // Status pending - não completado
        $this->task->setStatus('pending');
        $this->assertFalse($this->task->isCompleted());

        // Status in_progress - não completado
        $this->task->setStatus('in_progress');
        $this->assertFalse($this->task->isCompleted());

        // Status completed - completado
        $this->task->setStatus('completed');
        $this->assertTrue($this->task->isCompleted());

        // Status cancelled - não completado
        $this->task->setStatus('cancelled');
        $this->assertFalse($this->task->isCompleted());
    }

    /**
     * Testa método isInProgress
     */
    public function testIsInProgress(): void
    {
        $this->task->setStatus('in_progress');
        $this->assertTrue($this->task->isInProgress());

        $this->task->setStatus('pending');
        $this->assertFalse($this->task->isInProgress());

        $this->task->setStatus('completed');
        $this->assertFalse($this->task->isInProgress());
    }

    /**
     * Testa método isPending
     */
    public function testIsPending(): void
    {
        $this->task->setStatus('pending');
        $this->assertTrue($this->task->isPending());

        $this->task->setStatus('in_progress');
        $this->assertFalse($this->task->isPending());

        $this->task->setStatus('completed');
        $this->assertFalse($this->task->isPending());
    }

    /**
     * Testa método isOverdue
     */
    public function testIsOverdue(): void
    {
        // Sem data de vencimento - não está vencida
        $this->task->setDueDate(null);
        $this->assertFalse($this->task->isOverdue());

        // Data no futuro - não está vencida
        $futureDate = new DateTime('+7 days');
        $this->task->setDueDate($futureDate);
        $this->assertFalse($this->task->isOverdue());

        // Data no passado - está vencida
        $pastDate = new DateTime('-1 day');
        $this->task->setDueDate($pastDate);
        $this->assertTrue($this->task->isOverdue());

        // Tarefa completada não está vencida, mesmo com data no passado
        $this->task->setStatus('completed');
        $this->assertFalse($this->task->isOverdue());
    }

    /**
     * Testa comportamento da data de conclusão quando status muda
     */
    public function testCompletedAtAutoSetting(): void
    {
        // Inicialmente sem data de conclusão
        $this->assertNull($this->task->getCompletedAt());

        // Ao marcar como concluída, deve definir a data
        $beforeTime = new DateTime();
        $this->task->setStatus('completed');
        $afterTime = new DateTime();

        $this->assertNotNull($this->task->getCompletedAt());
        $this->assertTrue($this->task->getCompletedAt() >= $beforeTime);
        $this->assertTrue($this->task->getCompletedAt() <= $afterTime);

        // Ao mudar para outro status, deve limpar a data de conclusão
        $this->task->setStatus('pending');
        $this->assertNull($this->task->getCompletedAt());
    }

    /**
     * Testa método toArray
     */
    public function testToArray(): void
    {
        $this->task->setId(1);
        $this->task->setTitle('Tarefa de teste');
        $this->task->setDescription('Descrição da tarefa');
        $this->task->setStatus('in_progress');
        $this->task->setPriority('high');
        $this->task->setUserId(123);
        $this->task->setCategoryId(456);

        $dueDate = new DateTime('+7 days');
        $this->task->setDueDate($dueDate);

        $array = $this->task->toArray();

        $this->assertIsArray($array);
        $this->assertEquals(1, $array['id']);
        $this->assertEquals('Tarefa de teste', $array['title']);
        $this->assertEquals('Descrição da tarefa', $array['description']);
        $this->assertEquals('in_progress', $array['status']);
        $this->assertEquals('high', $array['priority']);
        $this->assertEquals(123, $array['user_id']);
        $this->assertEquals(456, $array['category_id']);
        $this->assertEquals($dueDate->format('Y-m-d H:i:s'), $array['due_date']);
        $this->assertArrayHasKey('created_at', $array);
        $this->assertArrayHasKey('updated_at', $array);
        $this->assertArrayHasKey('completed_at', $array);
        $this->assertArrayHasKey('is_completed', $array);
        $this->assertArrayHasKey('is_in_progress', $array);
        $this->assertArrayHasKey('is_pending', $array);
        $this->assertArrayHasKey('is_overdue', $array);

        // Verifica valores booleanos
        $this->assertFalse($array['is_completed']);
        $this->assertTrue($array['is_in_progress']);
        $this->assertFalse($array['is_pending']);
        $this->assertFalse($array['is_overdue']);
    }

    /**
     * Testa atualização do timestamp quando tarefa é modificada
     */
    public function testUpdatedAtTimestamp(): void
    {
        $initialTime = $this->task->getUpdatedAt();

        // Simula uma pequena pausa
        usleep(1000); // 1ms

        // Modifica a tarefa
        $this->task->setTitle('Novo título');

        // Verifica se updated_at foi atualizado
        $this->assertTrue($this->task->getUpdatedAt() > $initialTime);
    }

    /**
     * Testa se todas as modificações atualizam o timestamp
     */
    public function testTimestampUpdateOnChanges(): void
    {
        $this->task->setTitle('Teste');
        $this->task->setUserId(1);
        $initialTime = $this->task->getUpdatedAt();

        usleep(1000);

        // Testa várias modificações
        $modifications = [
            fn() => $this->task->setDescription('Nova descrição'),
            fn() => $this->task->setStatus('in_progress'),
            fn() => $this->task->setPriority('high'),
            fn() => $this->task->setDueDate(new DateTime('+1 day')),
            fn() => $this->task->setCategoryId(10),
        ];

        foreach ($modifications as $modification) {
            $timeBeforeChange = $this->task->getUpdatedAt();
            usleep(1000);
            $modification();
            $this->assertTrue($this->task->getUpdatedAt() > $timeBeforeChange);
        }
    }

    /**
     * Testa métodos estáticos para obter valores disponíveis
     */
    public function testStaticMethods(): void
    {
        $statuses = Task::getAvailableStatuses();
        $this->assertIsArray($statuses);
        $this->assertArrayHasKey('pending', $statuses);
        $this->assertArrayHasKey('in_progress', $statuses);
        $this->assertArrayHasKey('completed', $statuses);
        $this->assertArrayHasKey('cancelled', $statuses);

        $priorities = Task::getAvailablePriorities();
        $this->assertIsArray($priorities);
        $this->assertArrayHasKey('low', $priorities);
        $this->assertArrayHasKey('medium', $priorities);
        $this->assertArrayHasKey('high', $priorities);
        $this->assertArrayHasKey('urgent', $priorities);
    }

    /**
     * Testa constantes da classe
     */
    public function testConstants(): void
    {
        $this->assertEquals('pending', Task::STATUS_PENDING);
        $this->assertEquals('in_progress', Task::STATUS_IN_PROGRESS);
        $this->assertEquals('completed', Task::STATUS_COMPLETED);
        $this->assertEquals('cancelled', Task::STATUS_CANCELLED);

        $this->assertEquals('low', Task::PRIORITY_LOW);
        $this->assertEquals('medium', Task::PRIORITY_MEDIUM);
        $this->assertEquals('high', Task::PRIORITY_HIGH);
        $this->assertEquals('urgent', Task::PRIORITY_URGENT);
    }

    /**
     * Testa comportamento com valores extremos
     */
    public function testEdgeCases(): void
    {
        // Título exatamente com 3 caracteres (mínimo)
        $this->task->setTitle('abc');
        $this->assertEquals('abc', $this->task->getTitle());

        // Título com 200 caracteres (máximo)
        $maxTitle = str_repeat('a', 200);
        $this->task->setTitle($maxTitle);
        $this->assertEquals($maxTitle, $this->task->getTitle());

        // Descrição com 1000 caracteres (máximo)
        $maxDescription = str_repeat('a', 1000);
        $this->task->setDescription($maxDescription);
        $this->assertEquals($maxDescription, $this->task->getDescription());
    }

    /**
     * Testa fluent interface (method chaining)
     */
    public function testFluentInterface(): void
    {
        $dueDate = new DateTime('+1 week');

        $result = $this->task
            ->setId(1)
            ->setTitle('Tarefa fluente')
            ->setDescription('Descrição fluente')
            ->setStatus('in_progress')
            ->setPriority('high')
            ->setUserId(123)
            ->setCategoryId(456)
            ->setDueDate($dueDate);

        $this->assertSame($this->task, $result);
        $this->assertEquals(1, $this->task->getId());
        $this->assertEquals('Tarefa fluente', $this->task->getTitle());
        $this->assertEquals('Descrição fluente', $this->task->getDescription());
        $this->assertEquals('in_progress', $this->task->getStatus());
        $this->assertEquals('high', $this->task->getPriority());
        $this->assertEquals(123, $this->task->getUserId());
        $this->assertEquals(456, $this->task->getCategoryId());
        $this->assertEquals($dueDate, $this->task->getDueDate());
    }

    /**
     * Testa cenário completo de ciclo de vida da tarefa
     */
    public function testTaskLifecycle(): void
    {
        // Criação da tarefa
        $this->task->setTitle('Tarefa de ciclo de vida');
        $this->task->setUserId(1);
        $this->assertTrue($this->task->isPending());
        $this->assertFalse($this->task->isCompleted());

        // Início do trabalho
        $this->task->setStatus('in_progress');
        $this->assertTrue($this->task->isInProgress());
        $this->assertFalse($this->task->isPending());
        $this->assertFalse($this->task->isCompleted());

        // Conclusão da tarefa
        $beforeCompletion = new DateTime();
        $this->task->setStatus('completed');
        $afterCompletion = new DateTime();

        $this->assertTrue($this->task->isCompleted());
        $this->assertFalse($this->task->isInProgress());
        $this->assertFalse($this->task->isPending());
        $this->assertNotNull($this->task->getCompletedAt());
        $this->assertTrue($this->task->getCompletedAt() >= $beforeCompletion);
        $this->assertTrue($this->task->getCompletedAt() <= $afterCompletion);
    }

    /**
     * Testa trim de espaços em branco
     */
    public function testStringTrimming(): void
    {
        // Título com espaços
        $this->task->setTitle('  Título com espaços  ');
        $this->assertEquals('Título com espaços', $this->task->getTitle());

        // Descrição com espaços
        $this->task->setDescription('  Descrição com espaços  ');
        $this->assertEquals('Descrição com espaços', $this->task->getDescription());
    }
}
