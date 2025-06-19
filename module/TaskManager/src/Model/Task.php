<?php

namespace TaskManager\Model;

use DomainException;
use InvalidArgumentException;

class Task
{
    public $id;
    public $title;
    public $description;
    public $status;
    public $priority;
    public $due_date;
    public $completed_at;
    public $user_id;
    public $category_id;
    public $created_at;
    public $updated_at;

    public function exchangeArray(array $data)
    {
        $this->id = !empty($data['id']) ? $data['id'] : null;
        $this->title = !empty($data['title']) ? $data['title'] : null;
        $this->description = !empty($data['description']) ? $data['description'] : null;
        $this->status = !empty($data['status']) ? $data['status'] : 'pending';
        $this->priority = !empty($data['priority']) ? $data['priority'] : 'medium';
        $this->due_date = !empty($data['due_date']) ? $data['due_date'] : null;
        $this->completed_at = !empty($data['completed_at']) ? $data['completed_at'] : null;
        $this->user_id = !empty($data['user_id']) ? $data['user_id'] : null;
        $this->category_id = !empty($data['category_id']) ? $data['category_id'] : null;
        $this->created_at = !empty($data['created_at']) ? $data['created_at'] : null;
        $this->updated_at = !empty($data['updated_at']) ? $data['updated_at'] : null;
    }

    public function getArrayCopy()
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'priority' => $this->priority,
            'due_date' => $this->due_date,
            'completed_at' => $this->completed_at,
            'user_id' => $this->user_id,
            'category_id' => $this->category_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public function getStatusLabel()
    {
        $labels = [
            'pending' => 'Pendente',
            'in_progress' => 'Em Andamento',
            'completed' => 'Concluída',
            'cancelled' => 'Cancelada'
        ];
        
        return isset($labels[$this->status]) ? $labels[$this->status] : 'Desconhecido';
    }

    public function getPriorityLabel()
    {
        $labels = [
            'low' => 'Baixa',
            'medium' => 'Média',
            'high' => 'Alta',
            'urgent' => 'Urgente'
        ];
        
        return isset($labels[$this->priority]) ? $labels[$this->priority] : 'Média';
    }

    public function getPriorityClass()
    {
        $classes = [
            'low' => 'text-success',
            'medium' => 'text-info',
            'high' => 'text-warning',
            'urgent' => 'text-danger'
        ];
        
        return isset($classes[$this->priority]) ? $classes[$this->priority] : 'text-info';
    }

    public function isOverdue()
    {
        if (!$this->due_date || $this->status === 'completed') {
            return false;
        }
        
        return strtotime($this->due_date) < time();
    }
}