<?php

namespace TaskManager\Model;

use DomainException;
use InvalidArgumentException;
use DateTime;

class Task
{
    // Constantes para status válidos
    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    // Constantes para prioridades válidas
    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';

    // Propriedades da tarefa
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

    /**
     * Lista de status válidos
     */
    public static function getValidStatuses()
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_IN_PROGRESS,
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED
        ];
    }

    /**
     * Lista de prioridades válidas
     */
    public static function getValidPriorities()
    {
        return [
            self::PRIORITY_LOW,
            self::PRIORITY_MEDIUM,
            self::PRIORITY_HIGH,
            self::PRIORITY_URGENT
        ];
    }

    /**
     * Troca dados do array com validação
     */
    public function exchangeArray(array $data)
    {
        $this->id = !empty($data['id']) ? (int)$data['id'] : null;
        
        // Validar e definir título
        if (isset($data['title'])) {
            $this->setTitle($data['title']);
        }
        
        // Definir descrição
        $this->description = !empty($data['description']) ? trim($data['description']) : null;
        
        // Validar e definir status
        if (isset($data['status'])) {
            $this->setStatus($data['status']);
        } else {
            $this->status = self::STATUS_PENDING;
        }
        
        // Validar e definir prioridade
        if (isset($data['priority'])) {
            $this->setPriority($data['priority']);
        } else {
            $this->priority = self::PRIORITY_MEDIUM;
        }
        
        // Validar e definir data de vencimento
        if (isset($data['due_date'])) {
            $this->setDueDate($data['due_date']);
        }
        
        $this->completed_at = !empty($data['completed_at']) ? $data['completed_at'] : null;
        $this->user_id = !empty($data['user_id']) ? (int)$data['user_id'] : null;
        $this->category_id = !empty($data['category_id']) ? (int)$data['category_id'] : null;
        $this->created_at = !empty($data['created_at']) ? $data['created_at'] : null;
        $this->updated_at = !empty($data['updated_at']) ? $data['updated_at'] : null;
    }

    /**
     * Validar e definir título da tarefa
     */
    public function setTitle($title)
    {
        $title = trim($title);
        
        if (empty($title)) {
            throw new InvalidArgumentException('O título da tarefa não pode estar vazio.');
        }
        
        if (strlen($title) > 200) {
            throw new InvalidArgumentException('O título da tarefa não pode ter mais de 200 caracteres.');
        }
        
        if (strlen($title) < 3) {
            throw new InvalidArgumentException('O título da tarefa deve ter pelo menos 3 caracteres.');
        }
        
        $this->title = $title;
    }

    /**
     * Validar e definir status da tarefa
     */
    public function setStatus($status)
    {
        if (!in_array($status, self::getValidStatuses())) {
            throw new InvalidArgumentException(sprintf(
                'Status "%s" é inválido. Status válidos são: %s',
                $status,
                implode(', ', self::getValidStatuses())
            ));
        }
        
        $this->status = $status;
        
        // Automaticamente definir completed_at quando status for completed
        if ($status === self::STATUS_COMPLETED && !$this->completed_at) {
            $this->completed_at = date('Y-m-d H:i:s');
        } elseif ($status !== self::STATUS_COMPLETED) {
            $this->completed_at = null;
        }
    }

    /**
     * Validar e definir prioridade da tarefa
     */
    public function setPriority($priority)
    {
        if (!in_array($priority, self::getValidPriorities())) {
            throw new InvalidArgumentException(sprintf(
                'Prioridade "%s" é inválida. Prioridades válidas são: %s',
                $priority,
                implode(', ', self::getValidPriorities())
            ));
        }
        
        $this->priority = $priority;
    }

    /**
     * Validar e definir data de vencimento
     */
    public function setDueDate($dueDate)
    {
        if (empty($dueDate)) {
            $this->due_date = null;
            return;
        }
        
        // Validar formato de data
        if (!$this->isValidDateTime($dueDate)) {
            throw new InvalidArgumentException('Data de vencimento deve estar em um formato válido.');
        }
        
        $this->due_date = $dueDate;
    }

    /**
     * Verificar se uma string é uma data/hora válida
     */
    private function isValidDateTime($dateTime)
    {
        if (DateTime::createFromFormat('Y-m-d H:i:s', $dateTime) !== false) {
            return true;
        }
        
        if (DateTime::createFromFormat('Y-m-d\TH:i', $dateTime) !== false) {
            return true;
        }
        
        if (DateTime::createFromFormat('Y-m-d', $dateTime) !== false) {
            return true;
        }
        
        return false;
    }

    /**
     * Validar dados da tarefa
     */
    public function validate()
    {
        $errors = [];
        
        if (empty($this->title)) {
            $errors[] = 'O título da tarefa é obrigatório.';
        }
        
        if (empty($this->user_id)) {
            $errors[] = 'O usuário da tarefa é obrigatório.';
        }
        
        if (!empty($errors)) {
            throw new DomainException('Dados da tarefa são inválidos: ' . implode(' ', $errors));
        }
        
        return true;
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
            self::STATUS_PENDING => 'Pendente',
            self::STATUS_IN_PROGRESS => 'Em Andamento',
            self::STATUS_COMPLETED => 'Concluída',
            self::STATUS_CANCELLED => 'Cancelada'
        ];
        
        return isset($labels[$this->status]) ? $labels[$this->status] : 'Desconhecido';
    }

    public function getPriorityLabel()
    {
        $labels = [
            self::PRIORITY_LOW => 'Baixa',
            self::PRIORITY_MEDIUM => 'Média',
            self::PRIORITY_HIGH => 'Alta',
            self::PRIORITY_URGENT => 'Urgente'
        ];
        
        return isset($labels[$this->priority]) ? $labels[$this->priority] : 'Média';
    }

    public function getPriorityClass()
    {
        $classes = [
            self::PRIORITY_LOW => 'text-success',
            self::PRIORITY_MEDIUM => 'text-info',
            self::PRIORITY_HIGH => 'text-warning',
            self::PRIORITY_URGENT => 'text-danger'
        ];
        
        return isset($classes[$this->priority]) ? $classes[$this->priority] : 'text-info';
    }

    public function isOverdue()
    {
        if (!$this->due_date || $this->status === self::STATUS_COMPLETED) {
            return false;
        }
        
        return strtotime($this->due_date) < time();
    }

    /**
     * Verificar se a tarefa está concluída
     */
    public function isCompleted()
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Verificar se a tarefa está em andamento
     */
    public function isInProgress()
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    /**
     * Verificar se a tarefa está pendente
     */
    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Obter número de dias até o vencimento
     */
    public function getDaysUntilDue()
    {
        if (!$this->due_date) {
            return null;
        }
        
        $now = new DateTime();
        $dueDate = new DateTime($this->due_date);
        $interval = $now->diff($dueDate);
        
        return $interval->invert ? -$interval->days : $interval->days;
    }

    /**
     * Marcar tarefa como concluída
     */
    public function markAsCompleted()
    {
        $this->setStatus(self::STATUS_COMPLETED);
    }

    /**
     * Marcar tarefa como em andamento
     */
    public function markAsInProgress()
    {
        $this->setStatus(self::STATUS_IN_PROGRESS);
    }

    /**
     * Reiniciar tarefa para status pendente
     */
    public function markAsPending()
    {
        $this->setStatus(self::STATUS_PENDING);
    }
}