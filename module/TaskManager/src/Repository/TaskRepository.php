<?php

declare(strict_types=1);

namespace TaskManager\Repository;

use TaskManager\Entity\Task;
use TaskManager\Model\TaskTable;
use DateTime;

/**
 * Repositório para operações com tarefas usando Zend Db
 */
class TaskRepository implements TaskRepositoryInterface
{
    private TaskTable $taskTable;

    public function __construct(TaskTable $taskTable)
    {
        $this->taskTable = $taskTable;
    }

    /**
     * Busca todas as tarefas de um usuário
     */
    public function findByUserId(int $userId, array $filters = []): array
    {
        if (empty($filters)) {
            return $this->taskTable->fetchAll($userId);
        }
        return $this->taskTable->fetchWithFilters(array_merge(['user_id' => $userId], $filters));
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
     * Cria uma nova tarefa
     */
    public function create(Task $task): Task
    {
        return $this->taskTable->saveTask($task);
    }

    /**
     * Atualiza uma tarefa existente
     */
    public function update(Task $task): bool
    {
        $result = $this->taskTable->saveTask($task);
        return $result instanceof Task;
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
    public function findOverdueTasks(array $criteria = []): array
    {
        if (isset($criteria['user_id'])) {
            return $this->taskTable->findOverdueTasks($criteria['user_id']);
        }
        return $this->taskTable->findOverdueTasks(0); // Todas as tarefas vencidas
    }

    /**
     * Busca estatísticas das tarefas
     */
    public function getStatistics(int $userId = null): array
    {
        if ($userId !== null) {
            return $this->taskTable->getStatistics($userId);
        }
        return $this->taskTable->getStatistics(0);
    }

    /**
     * Salva uma tarefa (create ou update)
     */
    public function save(Task $task): Task
    {
        return $this->taskTable->saveTask($task);
    }

    /**
     * Busca tarefas por texto
     */
    public function search(string $searchTerm): array
    {
        return $this->taskTable->searchTasks($searchTerm);
    }

    /**
     * Busca tarefas por prioridade
     */
    public function findByPriority(string $priority, int $userId = null): array
    {
        $filters = ['priority' => $priority];
        if ($userId !== null) {
            $filters['user_id'] = $userId;
        }
        return $this->taskTable->fetchWithFilters($filters);
    }

    /**
     * Arquiva uma tarefa
     */
    public function archive(int $id): bool
    {
        return $this->taskTable->archiveTask($id);
    }

    /**
     * Restaura uma tarefa arquivada
     */
    public function restore(int $id): bool
    {
        return $this->taskTable->restoreTask($id);
    }

    /**
     * Busca tarefas por status
     */
    public function findByStatus(string $status, int $userId = null): array
    {
        $filters = ['status' => $status];
        if ($userId !== null) {
            $filters['user_id'] = $userId;
        }
        return $this->taskTable->fetchWithFilters($filters);
    }

    /**
     * Busca tarefas com filtros avançados
     */
    public function findWithFilters(array $filters, int $page = 1, int $limit = 10): array
    {
        return $this->taskTable->fetchWithPagination(
            $filters['user_id'] ?? null,
            $page,
            $limit,
            $filters
        );
    }

    /**
     * Conta tarefas
     */
    public function count(array $criteria = []): int
    {
        return $this->taskTable->countTasks($criteria);
    }

    /**
     * Atualiza o status de uma tarefa
     */
    public function updateStatus(int $id, string $status): bool
    {
        return $this->taskTable->updateTaskStatus($id, $status);
    }

    /**
     * Busca estatísticas das tarefas (método da interface)
     */
    public function getTaskStatistics(): array
    {
        return $this->taskTable->getStatistics(0);
    }

    /**
     * Busca tarefas por categoria
     */
    public function findByCategory(string $category, int $userId = null): array
    {
        $filters = ['category_id' => $category];
        if ($userId !== null) {
            $filters['user_id'] = $userId;
        }
        return $this->taskTable->fetchWithFilters($filters);
    }

    /**
     * Busca tarefas por data de vencimento
     */
    public function findByDueDate(\DateTime $date, int $userId = null): array
    {
        $filters = ['due_date' => $date->format('Y-m-d')];
        if ($userId !== null) {
            $filters['user_id'] = $userId;
        }
        return $this->taskTable->fetchWithFilters($filters);
    }
}
