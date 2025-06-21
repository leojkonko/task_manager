<?php

declare(strict_types=1);

namespace TaskManager\Model;

use Laminas\Db\TableGateway\TableGateway;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Where;
use Laminas\Db\Sql\Expression;
use TaskManager\Entity\Task;
use DateTime;

/**
 * Table Data Gateway para a tabela tasks
 */
class TaskTable
{
    private TableGateway $tableGateway;

    public function __construct(TableGateway $tableGateway)
    {
        $this->tableGateway = $tableGateway;
    }

    /**
     * Busca todas as tarefas de um usuário
     */
    public function fetchAll(int $userId): array
    {
        $select = $this->tableGateway->getSql()->select();
        $select->where(['user_id' => $userId])
            ->order('created_at DESC');

        $resultSet = $this->tableGateway->selectWith($select);
        return $this->resultSetToTaskArray($resultSet);
    }

    /**
     * Busca tarefas com filtros e paginação
     */
    public function fetchWithPagination(int $userId, int $page = 1, int $limit = 10, array $filters = []): array
    {
        $select = $this->tableGateway->getSql()->select();
        $countSelect = $this->tableGateway->getSql()->select();

        // Aplicar filtros
        $where = $this->buildWhereClause($userId, $filters);
        $select->where($where);
        $countSelect->where($where);

        // Contar total de registros
        $countSelect->columns(['total' => new Expression('COUNT(*)')]);
        $countResult = $this->tableGateway->selectWith($countSelect);
        $total = $countResult->current()['total'];

        // Aplicar ordenação
        $orderBy = $filters['order_by'] ?? 'created_at';
        $orderDirection = $filters['order_direction'] ?? 'DESC';
        $select->order($orderBy . ' ' . $orderDirection);

        // Aplicar paginação
        $offset = ($page - 1) * $limit;
        $select->limit($limit)->offset($offset);

        $resultSet = $this->tableGateway->selectWith($select);
        $tasks = $this->resultSetToTaskArray($resultSet);

        return [
            'tasks' => $tasks,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit),
        ];
    }

    /**
     * Busca uma tarefa pelo ID
     */
    public function getTask(int $id): ?Task
    {
        $rowset = $this->tableGateway->select(['id' => $id]);
        $row = $rowset->current();

        if (!$row) {
            return null;
        }

        return $this->arrayToTask($row->getArrayCopy());
    }

    /**
     * Salva uma tarefa (insert ou update)
     */
    public function saveTask(Task $task): Task
    {
        $data = $this->taskToArray($task);

        if ($task->getId()) {
            // Update
            $data['updated_at'] = date('Y-m-d H:i:s');
            unset($data['id'], $data['created_at']);

            $this->tableGateway->update($data, ['id' => $task->getId()]);
        } else {
            // Insert
            $now = date('Y-m-d H:i:s');
            $data['created_at'] = $now;
            $data['updated_at'] = $now;
            unset($data['id']);

            $this->tableGateway->insert($data);
            $task->setId((int) $this->tableGateway->getLastInsertValue());
        }

        return $this->getTask($task->getId());
    }

    /**
     * Exclui uma tarefa
     */
    public function deleteTask(int $id): bool
    {
        $result = $this->tableGateway->delete(['id' => $id]);
        return $result > 0;
    }

    /**
     * Busca tarefas vencidas de um usuário
     */
    public function findOverdueTasks(int $userId): array
    {
        $select = $this->tableGateway->getSql()->select();
        $select->where([
            'user_id' => $userId,
            'status != ?' => Task::STATUS_COMPLETED,
            'due_date IS NOT NULL',
            'due_date < ?' => date('Y-m-d H:i:s')
        ])->order('due_date ASC');

        $resultSet = $this->tableGateway->selectWith($select);
        return $this->resultSetToTaskArray($resultSet);
    }

    /**
     * Busca estatísticas das tarefas de um usuário
     */
    public function getStatistics(int $userId): array
    {
        // Total de tarefas
        $totalSelect = $this->tableGateway->getSql()->select();
        $totalSelect->where(['user_id' => $userId])
            ->columns(['total' => new Expression('COUNT(*)')]);
        $totalResult = $this->tableGateway->selectWith($totalSelect);
        $total = $totalResult->current()['total'];

        // Tarefas por status
        $statusSelect = $this->tableGateway->getSql()->select();
        $statusSelect->where(['user_id' => $userId])
            ->columns(['status', 'count' => new Expression('COUNT(*)')])
            ->group('status');
        $statusResult = $this->tableGateway->selectWith($statusSelect);

        $byStatus = [];
        foreach ($statusResult as $row) {
            $byStatus[$row['status']] = (int)$row['count'];
        }

        // Tarefas vencidas
        $overdueSelect = $this->tableGateway->getSql()->select();
        $overdueSelect->where([
            'user_id' => $userId,
            'status != ?' => Task::STATUS_COMPLETED,
            'due_date IS NOT NULL',
            'due_date < ?' => date('Y-m-d H:i:s')
        ])->columns(['overdue' => new Expression('COUNT(*)')]);
        $overdueResult = $this->tableGateway->selectWith($overdueSelect);
        $overdue = $overdueResult->current()['overdue'];

        return [
            'total' => $total,
            'by_status' => $byStatus,
            'overdue' => $overdue,
        ];
    }

    /**
     * Constrói cláusula WHERE com base nos filtros
     */
    private function buildWhereClause(int $userId, array $filters): Where
    {
        $where = new Where();
        $where->equalTo('user_id', $userId);

        if (!empty($filters['status'])) {
            $where->equalTo('status', $filters['status']);
        }

        if (!empty($filters['priority'])) {
            $where->equalTo('priority', $filters['priority']);
        }

        if (!empty($filters['category_id'])) {
            $where->equalTo('category_id', $filters['category_id']);
        }

        if (!empty($filters['search'])) {
            $where->like('title', '%' . $filters['search'] . '%');
        }

        return $where;
    }

    /**
     * Converte array do banco para objeto Task
     */
    private function arrayToTask(array $data): Task
    {
        $task = new Task();
        $task->setId($data['id'])
            ->setTitle($data['title'])
            ->setDescription($data['description'])
            ->setStatus($data['status'])
            ->setPriority($data['priority'])
            ->setUserId($data['user_id'])
            ->setCategoryId($data['category_id']);

        if ($data['due_date']) {
            $task->setDueDate(new DateTime($data['due_date']));
        }

        if ($data['completed_at']) {
            $task->setCompletedAt(new DateTime($data['completed_at']));
        }

        if ($data['created_at']) {
            $task->setCreatedAt(new DateTime($data['created_at']));
        }

        if ($data['updated_at']) {
            $task->setUpdatedAt(new DateTime($data['updated_at']));
        }

        return $task;
    }

    /**
     * Converte objeto Task para array
     */
    private function taskToArray(Task $task): array
    {
        return [
            'id' => $task->getId(),
            'title' => $task->getTitle(),
            'description' => $task->getDescription(),
            'status' => $task->getStatus(),
            'priority' => $task->getPriority(),
            'user_id' => $task->getUserId(),
            'category_id' => $task->getCategoryId(),
            'due_date' => $task->getDueDate() ? $task->getDueDate()->format('Y-m-d H:i:s') : null,
            'completed_at' => $task->getCompletedAt() ? $task->getCompletedAt()->format('Y-m-d H:i:s') : null,
            'created_at' => $task->getCreatedAt() ? $task->getCreatedAt()->format('Y-m-d H:i:s') : null,
            'updated_at' => $task->getUpdatedAt() ? $task->getUpdatedAt()->format('Y-m-d H:i:s') : null,
        ];
    }

    /**
     * Converte ResultSet para array de Tasks
     */
    private function resultSetToTaskArray($resultSet): array
    {
        $tasks = [];
        foreach ($resultSet as $row) {
            $tasks[] = $this->arrayToTask($row->getArrayCopy());
        }
        return $tasks;
    }

    /**
     * Busca tarefas com filtros
     */
    public function fetchWithFilters(array $filters): array
    {
        $select = $this->tableGateway->getSql()->select();
        $where = new Where();

        // Aplicar filtros
        foreach ($filters as $field => $value) {
            if ($value !== null && $value !== '') {
                switch ($field) {
                    case 'search':
                        $where->nest()
                            ->like('title', '%' . $value . '%')
                            ->or
                            ->like('description', '%' . $value . '%')
                            ->unnest();
                        break;
                    case 'status':
                    case 'priority':
                    case 'user_id':
                    case 'category_id':
                        $where->equalTo($field, $value);
                        break;
                    case 'due_date':
                        $where->like('due_date', $value . '%');
                        break;
                }
            }
        }

        $select->where($where)->order('created_at DESC');
        
        $resultSet = $this->tableGateway->selectWith($select);
        return $this->resultSetToTaskArray($resultSet);
    }

    /**
     * Busca tarefas por texto
     */
    public function searchTasks(string $searchTerm): array
    {
        return $this->fetchWithFilters(['search' => $searchTerm]);
    }

    /**
     * Conta tarefas
     */
    public function countTasks(array $criteria = []): int
    {
        $select = $this->tableGateway->getSql()->select();
        $select->columns(['count' => new Expression('COUNT(*)')]);
        
        if (!empty($criteria)) {
            $select->where($criteria);
        }
        
        $resultSet = $this->tableGateway->selectWith($select);
        $result = $resultSet->current();
        
        return (int) $result['count'];
    }

    /**
     * Arquiva uma tarefa
     */
    public function archiveTask(int $id): bool
    {
        $data = ['archived' => 1, 'archived_at' => date('Y-m-d H:i:s')];
        $result = $this->tableGateway->update($data, ['id' => $id]);
        return $result > 0;
    }

    /**
     * Restaura uma tarefa arquivada
     */
    public function restoreTask(int $id): bool
    {
        $data = ['archived' => 0, 'archived_at' => null];
        $result = $this->tableGateway->update($data, ['id' => $id]);
        return $result > 0;
    }

    /**
     * Atualiza status da tarefa
     */
    public function updateTaskStatus(int $id, string $status): bool
    {
        $data = ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')];
        
        if ($status === Task::STATUS_COMPLETED) {
            $data['completed_at'] = date('Y-m-d H:i:s');
        } elseif ($status !== Task::STATUS_COMPLETED) {
            $data['completed_at'] = null;
        }
        
        $result = $this->tableGateway->update($data, ['id' => $id]);
        return $result > 0;
    }

    /**
     * Find tasks that are due within specified hours and need reminders
     */
    public function findTasksDueWithinHours(int $hours, bool $reminderSent = false): array
    {
        $select = $this->tableGateway->getSql()->select();
        
        // Calculate target datetime
        $targetDateTime = new DateTime();
        $targetDateTime->modify("+{$hours} hours");
        
        $where = new Where();
        $where->equalTo('status', Task::STATUS_PENDING)
            ->isNotNull('due_date')
            ->lessThanOrEqualTo('due_date', $targetDateTime->format('Y-m-d H:i:s'))
            ->greaterThan('due_date', date('Y-m-d H:i:s'))  // Not overdue yet
            ->equalTo('reminder_sent', $reminderSent ? 1 : 0);
        
        $select->where($where)
            ->join('users', 'tasks.user_id = users.id', ['email', 'full_name'])
            ->order('due_date ASC');
        
        $resultSet = $this->tableGateway->selectWith($select);
        return $this->resultSetToTaskArrayWithUser($resultSet);
    }

    /**
     * Find all overdue tasks (for notifications)
     */
    public function findAllOverdueTasks(): array
    {
        $select = $this->tableGateway->getSql()->select();
        
        $where = new Where();
        $where->in('status', [Task::STATUS_PENDING, Task::STATUS_IN_PROGRESS])
            ->isNotNull('due_date')
            ->lessThan('due_date', date('Y-m-d H:i:s'));
        
        $select->where($where)
            ->join('users', 'tasks.user_id = users.id', ['email', 'full_name'])
            ->order('due_date ASC');
        
        $resultSet = $this->tableGateway->selectWith($select);
        return $this->resultSetToTaskArrayWithUser($resultSet);
    }

    /**
     * Mark that a reminder has been sent for a task
     */
    public function markReminderSent(int $taskId): bool
    {
        $data = [
            'reminder_sent' => 1,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $result = $this->tableGateway->update($data, ['id' => $taskId]);
        return $result > 0;
    }

    /**
     * Convert ResultSet to array of Tasks with user information
     */
    private function resultSetToTaskArrayWithUser($resultSet): array
    {
        $tasks = [];
        foreach ($resultSet as $row) {
            $rowData = $row->getArrayCopy();
            $task = $this->arrayToTask($rowData);
            
            // Add user information to task
            if (isset($rowData['email']) && isset($rowData['full_name'])) {
                $user = new \Auth\Model\User();
                $user->setEmail($rowData['email']);
                $user->setFullName($rowData['full_name']);
                $task->setUser($user);
            }
            
            $tasks[] = $task;
        }
        return $tasks;
    }
}
