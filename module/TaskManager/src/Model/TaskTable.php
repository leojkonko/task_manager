<?php

namespace TaskManager\Model;

use RuntimeException;
use Laminas\Db\TableGateway\TableGatewayInterface;

class TaskTable
{
    private $tableGateway;

    public function __construct(TableGatewayInterface $tableGateway)
    {
        $this->tableGateway = $tableGateway;
    }

    public function fetchAll($userId = null)
    {
        if ($userId) {
            return $this->tableGateway->select(['user_id' => $userId]);
        }
        return $this->tableGateway->select();
    }

    public function getTask($id)
    {
        $id = (int) $id;
        $rowset = $this->tableGateway->select(['id' => $id]);
        $row = $rowset->current();
        if (! $row) {
            throw new RuntimeException(sprintf(
                'Could not find row with identifier %d',
                $id
            ));
        }

        return $row;
    }

    public function saveTask(Task $task)
    {
        $data = [
            'title' => $task->title,
            'description' => $task->description,
            'status' => $task->status,
            'priority' => $task->priority,
            'due_date' => $task->due_date,
            'user_id' => $task->user_id,
            'category_id' => $task->category_id,
        ];

        $id = (int) $task->id;

        if ($id === 0) {
            $this->tableGateway->insert($data);
            return;
        }

        try {
            $this->getTask($id);
        } catch (RuntimeException $e) {
            throw new RuntimeException(sprintf(
                'Cannot update task with identifier %d; does not exist',
                $id
            ));
        }

        // Atualizar completed_at se status mudou para completed
        if ($task->status === 'completed' && !$task->completed_at) {
            $data['completed_at'] = date('Y-m-d H:i:s');
        } elseif ($task->status !== 'completed') {
            $data['completed_at'] = null;
        }

        $this->tableGateway->update($data, ['id' => $id]);
    }

    public function deleteTask($id)
    {
        $this->tableGateway->delete(['id' => (int) $id]);
    }

    public function getTasksByStatus($status, $userId = null)
    {
        $where = ['status' => $status];
        if ($userId) {
            $where['user_id'] = $userId;
        }
        return $this->tableGateway->select($where);
    }

    public function getTasksByCategory($categoryId, $userId = null)
    {
        $where = ['category_id' => $categoryId];
        if ($userId) {
            $where['user_id'] = $userId;
        }
        return $this->tableGateway->select($where);
    }

    public function getOverdueTasks($userId = null)
    {
        $sql = $this->tableGateway->getSql();
        $select = $sql->select();

        $select->where([
            'due_date < ?' => date('Y-m-d H:i:s'),
            'status != ?' => 'completed'
        ]);

        if ($userId) {
            $select->where(['user_id' => $userId]);
        }

        return $this->tableGateway->selectWith($select);
    }
}
