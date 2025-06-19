<?php

declare(strict_types=1);

namespace TaskManager\Repository;

use TaskManager\Entity\Task;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Select;
use Laminas\Db\ResultSet\ResultSet;
use DateTime;

/**
 * Repository para gerenciar operações de banco de dados das tarefas
 */
class TaskRepository
{
    private AdapterInterface $adapter;
    private string $tableName = 'tasks';

    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * Salva uma nova tarefa no banco de dados
     */
    public function save(Task $task): Task
    {
        $sql = new Sql($this->adapter);
        
        $data = [
            'title' => $task->getTitle(),
            'description' => $task->getDescription(),
            'status' => $task->getStatus(),
            'priority' => $task->getPriority(),
            'due_date' => $task->getDueDate()?->format('Y-m-d H:i:s'),
            'completed_at' => $task->getCompletedAt()?->format('Y-m-d H:i:s'),
            'user_id' => $task->getUserId(),
            'category_id' => $task->getCategoryId(),
            'updated_at' => (new DateTime())->format('Y-m-d H:i:s'),
        ];

        if ($task->getId() === null) {
            // Insert
            $data['created_at'] = (new DateTime())->format('Y-m-d H:i:s');
            $insert = $sql->insert($this->tableName);
            $insert->values($data);
            
            $statement = $sql->prepareStatementForSqlObject($insert);
            $result = $statement->execute();
            
            $task->setId((int) $result->getGeneratedValue());
        } else {
            // Update
            $update = $sql->update($this->tableName);
            $update->set($data);
            $update->where(['id' => $task->getId()]);
            
            $statement = $sql->prepareStatementForSqlObject($update);
            $statement->execute();
        }

        return $task;
    }

    /**
     * Busca uma tarefa pelo ID
     */
    public function findById(int $id): ?Task
    {
        $sql = new Sql($this->adapter);
        $select = $sql->select($this->tableName);
        $select->where(['id' => $id]);

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        if ($result->isQueryResult() && $result->getAffectedRows()) {
            $row = $result->current();
            return $this->hydrate($row);
        }

        return null;
    }

    /**
     * Busca todas as tarefas de um usuário
     */
    public function findByUserId(int $userId, array $filters = []): array
    {
        $sql = new Sql($this->adapter);
        $select = $sql->select($this->tableName);
        $select->where(['user_id' => $userId]);

        // Aplicar filtros se fornecidos
        if (isset($filters['status'])) {
            $select->where(['status' => $filters['status']]);
        }

        if (isset($filters['priority'])) {
            $select->where(['priority' => $filters['priority']]);
        }

        if (isset($filters['category_id'])) {
            $select->where(['category_id' => $filters['category_id']]);
        }

        // Ordenação padrão
        $orderBy = $filters['order_by'] ?? 'created_at';
        $orderDirection = $filters['order_direction'] ?? 'DESC';
        $select->order($orderBy . ' ' . $orderDirection);

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        $tasks = [];
        if ($result->isQueryResult()) {
            foreach ($result as $row) {
                $tasks[] = $this->hydrate($row);
            }
        }

        return $tasks;
    }

    /**
     * Busca tarefas com paginação
     */
    public function findWithPagination(int $userId, int $page = 1, int $limit = 10, array $filters = []): array
    {
        $offset = ($page - 1) * $limit;

        $sql = new Sql($this->adapter);
        $select = $sql->select($this->tableName);
        $select->where(['user_id' => $userId]);

        // Aplicar filtros
        if (isset($filters['status'])) {
            $select->where(['status' => $filters['status']]);
        }

        if (isset($filters['priority'])) {
            $select->where(['priority' => $filters['priority']]);
        }

        if (isset($filters['category_id'])) {
            $select->where(['category_id' => $filters['category_id']]);
        }

        if (isset($filters['search'])) {
            $select->where->like('title', '%' . $filters['search'] . '%');
        }

        // Contar total de registros
        $countSelect = clone $select;
        $countSelect->columns(['count' => new \Laminas\Db\Sql\Expression('COUNT(*)')]);
        $countStatement = $sql->prepareStatementForSqlObject($countSelect);
        $countResult = $countStatement->execute();
        $totalRecords = $countResult->current()['count'];

        // Aplicar paginação
        $orderBy = $filters['order_by'] ?? 'created_at';
        $orderDirection = $filters['order_direction'] ?? 'DESC';
        $select->order($orderBy . ' ' . $orderDirection);
        $select->limit($limit);
        $select->offset($offset);

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        $tasks = [];
        if ($result->isQueryResult()) {
            foreach ($result as $row) {
                $tasks[] = $this->hydrate($row);
            }
        }

        return [
            'tasks' => $tasks,
            'total' => $totalRecords,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($totalRecords / $limit),
        ];
    }

    /**
     * Exclui uma tarefa
     */
    public function delete(int $id): bool
    {
        $sql = new Sql($this->adapter);
        $delete = $sql->delete($this->tableName);
        $delete->where(['id' => $id]);

        $statement = $sql->prepareStatementForSqlObject($delete);
        $result = $statement->execute();

        return $result->getAffectedRows() > 0;
    }

    /**
     * Busca tarefas vencidas
     */
    public function findOverdueTasks(int $userId): array
    {
        $sql = new Sql($this->adapter);
        $select = $sql->select($this->tableName);
        $select->where(['user_id' => $userId]);
        $select->where->lessThan('due_date', (new DateTime())->format('Y-m-d H:i:s'));
        $select->where->notIn('status', [Task::STATUS_COMPLETED, Task::STATUS_CANCELLED]);
        $select->order('due_date ASC');

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        $tasks = [];
        if ($result->isQueryResult()) {
            foreach ($result as $row) {
                $tasks[] = $this->hydrate($row);
            }
        }

        return $tasks;
    }

    /**
     * Busca estatísticas das tarefas
     */
    public function getStatistics(int $userId): array
    {
        $sql = new Sql($this->adapter);
        
        // Total de tarefas
        $select = $sql->select($this->tableName);
        $select->columns(['count' => new \Laminas\Db\Sql\Expression('COUNT(*)')]);
        $select->where(['user_id' => $userId]);
        
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        $total = $result->current()['count'];

        // Tarefas por status
        $statusStats = [];
        foreach ([Task::STATUS_PENDING, Task::STATUS_IN_PROGRESS, Task::STATUS_COMPLETED, Task::STATUS_CANCELLED] as $status) {
            $select = $sql->select($this->tableName);
            $select->columns(['count' => new \Laminas\Db\Sql\Expression('COUNT(*)')]);
            $select->where(['user_id' => $userId, 'status' => $status]);
            
            $statement = $sql->prepareStatementForSqlObject($select);
            $result = $statement->execute();
            $statusStats[$status] = $result->current()['count'];
        }

        // Tarefas vencidas
        $select = $sql->select($this->tableName);
        $select->columns(['count' => new \Laminas\Db\Sql\Expression('COUNT(*)')]);
        $select->where(['user_id' => $userId]);
        $select->where->lessThan('due_date', (new DateTime())->format('Y-m-d H:i:s'));
        $select->where->notIn('status', [Task::STATUS_COMPLETED, Task::STATUS_CANCELLED]);
        
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        $overdue = $result->current()['count'];

        return [
            'total' => $total,
            'by_status' => $statusStats,
            'overdue' => $overdue,
        ];
    }

    /**
     * Converte uma linha do banco em objeto Task
     */
    private function hydrate(array $row): Task
    {
        $task = new Task();
        $task->setId((int) $row['id']);
        $task->setTitle($row['title']);
        $task->setDescription($row['description']);
        $task->setStatus($row['status']);
        $task->setPriority($row['priority']);
        $task->setUserId((int) $row['user_id']);
        $task->setCategoryId($row['category_id'] ? (int) $row['category_id'] : null);

        if ($row['due_date']) {
            $task->setDueDate(new DateTime($row['due_date']));
        }

        if ($row['completed_at']) {
            $task->setCompletedAt(new DateTime($row['completed_at']));
        }

        if ($row['created_at']) {
            $task->setCreatedAt(new DateTime($row['created_at']));
        }

        if ($row['updated_at']) {
            $task->setUpdatedAt(new DateTime($row['updated_at']));
        }

        return $task;
    }
}