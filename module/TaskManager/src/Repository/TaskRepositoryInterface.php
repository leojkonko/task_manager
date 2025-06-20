<?php

declare(strict_types=1);

namespace TaskManager\Repository;

use TaskManager\Entity\Task;

/**
 * Interface para repositório de tarefas
 */
interface TaskRepositoryInterface
{
    /**
     * Busca todas as tarefas de um usuário
     */
    public function findByUserId(int $userId): array;

    /**
     * Busca uma tarefa por ID
     */
    public function findById(int $id): ?Task;

    /**
     * Cria uma nova tarefa
     */
    public function create(Task $task): Task;

    /**
     * Atualiza uma tarefa existente
     */
    public function update(Task $task): bool;

    /**
     * Exclui uma tarefa
     */
    public function delete(int $id): bool;

    /**
     * Busca tarefas com paginação e filtros
     */
    public function findWithPagination(int $userId, int $page, int $limit, array $filters = []): array;

    /**
     * Busca tarefas vencidas
     */
    public function findOverdueTasks(array $criteria = []): array;

    /**
     * Busca estatísticas das tarefas
     */
    public function getStatistics(int $userId = null): array;

    /**
     * Busca estatísticas das tarefas (método adicional)
     */
    public function getTaskStatistics(): array;

    /**
     * Busca tarefas por categoria
     */
    public function findByCategory(string $category, int $userId = null): array;

    /**
     * Busca tarefas por data de vencimento
     */
    public function findByDueDate(\DateTime $date, int $userId = null): array;

    /**
     * Salva uma tarefa (create ou update)
     */
    public function save(Task $task): Task;

    /**
     * Busca tarefas por texto
     */
    public function search(string $searchTerm): array;

    /**
     * Busca tarefas por prioridade
     */
    public function findByPriority(string $priority, int $userId = null): array;

    /**
     * Arquiva uma tarefa
     */
    public function archive(int $id): bool;

    /**
     * Restaura uma tarefa arquivada
     */
    public function restore(int $id): bool;

    /**
     * Busca tarefas com filtros avançados
     */
    public function findWithFilters(array $filters, int $page = 1, int $limit = 10): array;

    /**
     * Conta tarefas por critério
     */
    public function count(array $criteria = []): int;
}
