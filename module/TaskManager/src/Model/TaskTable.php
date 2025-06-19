<?php

namespace TaskManager\Model;

use RuntimeException;
use Laminas\Db\TableGateway\TableGatewayInterface;
use Laminas\Db\Sql\Select;
use Exception;

class TaskTable
{
    private $tableGateway;

    public function __construct(TableGatewayInterface $tableGateway)
    {
        $this->tableGateway = $tableGateway;
    }

    /**
     * Buscar todas as tarefas de um usuário
     */
    public function fetchAll($userId = null)
    {
        if ($userId) {
            return $this->tableGateway->select(['user_id' => $userId]);
        }
        return $this->tableGateway->select();
    }

    /**
     * Obter uma tarefa específica por ID
     */
    public function getTask($id)
    {
        $id = (int) $id;
        
        if ($id <= 0) {
            throw new RuntimeException('ID da tarefa deve ser um número positivo.');
        }
        
        $rowset = $this->tableGateway->select(['id' => $id]);
        $row = $rowset->current();
        
        if (!$row) {
            throw new RuntimeException(sprintf(
                'Tarefa com ID %d não foi encontrada.',
                $id
            ));
        }

        return $row;
    }

    /**
     * Salvar uma tarefa (criar ou atualizar)
     */
    public function saveTask(Task $task)
    {
        // Validar a tarefa antes de salvar
        $task->validate();
        
        $data = [
            'title' => $task->title,
            'description' => $task->description,
            'status' => $task->status,
            'priority' => $task->priority,
            'due_date' => $task->due_date,
            'user_id' => $task->user_id,
            'category_id' => $task->category_id,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $id = (int) $task->id;

        if ($id === 0) {
            // Criando nova tarefa
            $data['created_at'] = date('Y-m-d H:i:s');
            
            try {
                $result = $this->tableGateway->insert($data);
                if (!$result) {
                    throw new RuntimeException('Falha ao inserir nova tarefa no banco de dados.');
                }
                
                // Obter o ID da tarefa recém-criada
                $task->id = $this->tableGateway->getLastInsertValue();
                return $task;
                
            } catch (Exception $e) {
                throw new RuntimeException('Erro ao criar tarefa: ' . $e->getMessage());
            }
        }

        // Atualizando tarefa existente
        try {
            $this->getTask($id); // Verificar se a tarefa existe
        } catch (RuntimeException $e) {
            throw new RuntimeException(sprintf(
                'Não é possível atualizar a tarefa com ID %d; ela não existe.',
                $id
            ));
        }

        // Atualizar completed_at se status mudou para completed
        if ($task->status === Task::STATUS_COMPLETED && !$task->completed_at) {
            $data['completed_at'] = date('Y-m-d H:i:s');
        } elseif ($task->status !== Task::STATUS_COMPLETED) {
            $data['completed_at'] = null;
        }

        try {
            $result = $this->tableGateway->update($data, ['id' => $id]);
            if (!$result) {
                throw new RuntimeException('Falha ao atualizar tarefa no banco de dados.');
            }
            return $task;
            
        } catch (Exception $e) {
            throw new RuntimeException('Erro ao atualizar tarefa: ' . $e->getMessage());
        }
    }

    /**
     * Excluir uma tarefa
     */
    public function deleteTask($id)
    {
        $id = (int) $id;
        
        if ($id <= 0) {
            throw new RuntimeException('ID da tarefa deve ser um número positivo.');
        }
        
        // Verificar se a tarefa existe antes de excluir
        $this->getTask($id);
        
        try {
            $result = $this->tableGateway->delete(['id' => $id]);
            if (!$result) {
                throw new RuntimeException('Falha ao excluir tarefa do banco de dados.');
            }
            return true;
            
        } catch (Exception $e) {
            throw new RuntimeException('Erro ao excluir tarefa: ' . $e->getMessage());
        }
    }

    /**
     * Buscar tarefas por status
     */
    public function getTasksByStatus($status, $userId = null)
    {
        // Validar status
        if (!in_array($status, Task::getValidStatuses())) {
            throw new RuntimeException('Status fornecido é inválido.');
        }
        
        $where = ['status' => $status];
        if ($userId) {
            $where['user_id'] = (int)$userId;
        }
        
        return $this->tableGateway->select($where);
    }

    /**
     * Buscar tarefas por categoria
     */
    public function getTasksByCategory($categoryId, $userId = null)
    {
        $categoryId = (int)$categoryId;
        
        if ($categoryId <= 0) {
            throw new RuntimeException('ID da categoria deve ser um número positivo.');
        }
        
        $where = ['category_id' => $categoryId];
        if ($userId) {
            $where['user_id'] = (int)$userId;
        }
        
        return $this->tableGateway->select($where);
    }

    /**
     * Buscar tarefas atrasadas
     */
    public function getOverdueTasks($userId = null)
    {
        $sql = $this->tableGateway->getSql();
        $select = $sql->select();
        
        $select->where([
            'due_date < ?' => date('Y-m-d H:i:s'),
            'status != ?' => Task::STATUS_COMPLETED
        ]);

        if ($userId) {
            $select->where(['user_id' => (int)$userId]);
        }

        return $this->tableGateway->selectWith($select);
    }

    /**
     * Buscar tarefas por prioridade
     */
    public function getTasksByPriority($priority, $userId = null)
    {
        // Validar prioridade
        if (!in_array($priority, Task::getValidPriorities())) {
            throw new RuntimeException('Prioridade fornecida é inválida.');
        }
        
        $where = ['priority' => $priority];
        if ($userId) {
            $where['user_id'] = (int)$userId;
        }
        
        return $this->tableGateway->select($where);
    }

    /**
     * Contar tarefas por status para um usuário
     */
    public function countTasksByStatus($userId = null)
    {
        $counts = [
            Task::STATUS_PENDING => 0,
            Task::STATUS_IN_PROGRESS => 0,
            Task::STATUS_COMPLETED => 0,
            Task::STATUS_CANCELLED => 0,
        ];
        
        foreach ($counts as $status => $count) {
            $where = ['status' => $status];
            if ($userId) {
                $where['user_id'] = (int)$userId;
            }
            
            $rowset = $this->tableGateway->select($where);
            $counts[$status] = $rowset->count();
        }
        
        return $counts;
    }

    /**
     * Buscar tarefas com filtros múltiplos
     */
    public function findTasksWithFilters(array $filters = [])
    {
        $sql = $this->tableGateway->getSql();
        $select = $sql->select();
        
        if (isset($filters['user_id'])) {
            $select->where(['user_id' => (int)$filters['user_id']]);
        }
        
        if (isset($filters['status'])) {
            $select->where(['status' => $filters['status']]);
        }
        
        if (isset($filters['priority'])) {
            $select->where(['priority' => $filters['priority']]);
        }
        
        if (isset($filters['category_id'])) {
            $select->where(['category_id' => (int)$filters['category_id']]);
        }
        
        if (isset($filters['overdue']) && $filters['overdue']) {
            $select->where([
                'due_date < ?' => date('Y-m-d H:i:s'),
                'status != ?' => Task::STATUS_COMPLETED
            ]);
        }
        
        if (isset($filters['search']) && !empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $select->where->like('title', $search)->or->like('description', $search);
        }
        
        // Ordenação
        $orderBy = $filters['order_by'] ?? 'created_at';
        $orderDirection = $filters['order_direction'] ?? 'DESC';
        $select->order($orderBy . ' ' . $orderDirection);
        
        return $this->tableGateway->selectWith($select);
    }

    /**
     * Marcar múltiplas tarefas como concluídas
     */
    public function markTasksAsCompleted(array $taskIds, $userId = null)
    {
        if (empty($taskIds)) {
            throw new RuntimeException('Lista de IDs de tarefas não pode estar vazia.');
        }
        
        $data = [
            'status' => Task::STATUS_COMPLETED,
            'completed_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        
        $where = ['id' => $taskIds];
        if ($userId) {
            $where['user_id'] = (int)$userId;
        }
        
        try {
            $result = $this->tableGateway->update($data, $where);
            return $result;
        } catch (Exception $e) {
            throw new RuntimeException('Erro ao marcar tarefas como concluídas: ' . $e->getMessage());
        }
    }
}