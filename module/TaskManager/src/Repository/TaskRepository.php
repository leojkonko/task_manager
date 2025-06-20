<?php

declare(strict_types=1);

namespace TaskManager\Repository;

use TaskManager\Entity\Task;
use TaskManager\Model\TaskTable;
use DateTime;

/**
 * Repositório para operações com tarefas usando Zend Db
 */
class TaskRepository
{
    private TaskTable $taskTable;

    public function __construct(TaskTable $taskTable)
    {
        $this->taskTable = $taskTable;
    }

    /**
     * Busca todas as tarefas de um usuário
     */
    public function findByUserId(int $userId): array
    {
        return $this->taskTable->fetchAll($userId);
    }

    /**
     * Busca tarefas com paginação e filtros
     */
    public function findWithPagination(int $userId, int $page, int $limit, array $filters = []): array
    {
        return $this->taskTable->fetchWithPagination($userId, $page, $limit, $filters);
    }

    /**
     * Busca uma tarefa pelo ID
     */
    public function findById(int $id): ?Task
    {
        return $this->taskTable->getTask($id);
    }

    /**
     * Salva uma tarefa (insert ou update)
     */
    public function save(Task $task): Task
    {
        return $this->taskTable->saveTask($task);
    }

    /**
     * Exclui uma tarefa
     */
    public function delete(int $id): bool
    {
        return $this->taskTable->deleteTask($id);
    }

    /**
     * Busca tarefas vencidas
     */
    public function findOverdueTasks(int $userId): array
    {
        return $this->taskTable->findOverdueTasks($userId);
    }

    /**
     * Retorna estatísticas das tarefas
     */
    public function getStatistics(int $userId): array
    {
        return $this->taskTable->getStatistics($userId);
    }

    /**
     * Busca tarefas por status
     */
    public function findByStatus(int $userId, string $status): array
    {
        return $this->taskTable->fetchWithPagination($userId, 1, 1000, ['status' => $status])['tasks'];
    }

    /**
     * Busca tarefas por prioridade
     */
    public function findByPriority(int $userId, string $priority): array
    {
        return $this->taskTable->fetchWithPagination($userId, 1, 1000, ['priority' => $priority])['tasks'];
    }

    /**
     * Conta total de tarefas de um usuário
     */
    public function countByUserId(int $userId): int
    {
        return $this->taskTable->getStatistics($userId)['total'];
    }
}
