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
    public static function getValidStatuses(): array
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
    public static function getValidPriorities(): array
    {
        return [
            self::PRIORITY_LOW,
            self::PRIORITY_MEDIUM,
            self::PRIORITY_HIGH,
            self::PRIORITY_URGENT
        ];
    }

    /**
     * Validar os dados da tarefa
     */
    public function validate(): array
    {
        $errors = [];

        // Validar título (obrigatório)
        if (empty(trim($this->title))) {
            $errors['title'] = 'O título da tarefa é obrigatório.';
        } elseif (strlen(trim($this->title)) < 3) {
            $errors['title'] = 'O título deve ter pelo menos 3 caracteres.';
        } elseif (strlen(trim($this->title)) > 200) {
            $errors['title'] = 'O título não pode ter mais de 200 caracteres.';
        }

        // Validar descrição (opcional, mas se fornecida, deve ter tamanho válido)
        if (!empty($this->description) && strlen($this->description) > 2000) {
            $errors['description'] = 'A descrição não pode ter mais de 2000 caracteres.';
        }

        // Validar status
        if (!in_array($this->status, self::getValidStatuses())) {
            $errors['status'] = 'Status inválido.';
        }

        // Validar prioridade
        if (!in_array($this->priority, self::getValidPriorities())) {
            $errors['priority'] = 'Prioridade inválida.';
        }

        // Validar data de vencimento (se fornecida)
        if (!empty($this->due_date)) {
            $date = DateTime::createFromFormat('Y-m-d H:i:s', $this->due_date);
            if (!$date) {
                // Tentar formato alternativo
                $date = DateTime::createFromFormat('Y-m-d\TH:i', $this->due_date);
                if (!$date) {
                    $errors['due_date'] = 'Formato de data inválido.';
                }
            }
        }

        // Validar user_id (obrigatório)
        if (empty($this->user_id) || !is_numeric($this->user_id)) {
            $errors['user_id'] = 'ID do usuário é obrigatório e deve ser numérico.';
        }

        return $errors;
    }

    /**
     * Verificar se a tarefa é válida
     */
    public function isValid(): bool
    {
        return empty($this->validate());
    }

    public function exchangeArray(array $data)
    {
        $this->id = !empty($data['id']) ? (int) $data['id'] : null;
        $this->title = isset($data['title']) ? trim($data['title']) : null;
        $this->description = isset($data['description']) ? trim($data['description']) : null;
        $this->status = !empty($data['status']) ? $data['status'] : self::STATUS_PENDING;
        $this->priority = !empty($data['priority']) ? $data['priority'] : self::PRIORITY_MEDIUM;
        $this->due_date = !empty($data['due_date']) ? $this->formatDate($data['due_date']) : null;
        $this->completed_at = !empty($data['completed_at']) ? $data['completed_at'] : null;
        $this->user_id = !empty($data['user_id']) ? (int) $data['user_id'] : null;
        $this->category_id = !empty($data['category_id']) ? (int) $data['category_id'] : null;
        $this->created_at = !empty($data['created_at']) ? $data['created_at'] : null;
        $this->updated_at = !empty($data['updated_at']) ? $data['updated_at'] : null;
    }

    /**
     * Formatar data para o formato MySQL
     */
    private function formatDate($date): ?string
    {
        if (empty($date)) {
            return null;
        }

        // Se já está no formato correto
        if (DateTime::createFromFormat('Y-m-d H:i:s', $date)) {
            return $date;
        }

        // Tentar formato datetime-local (HTML5)
        $dateObj = DateTime::createFromFormat('Y-m-d\TH:i', $date);
        if ($dateObj) {
            return $dateObj->format('Y-m-d H:i:s');
        }

        // Tentar outros formatos comuns
        $formats = ['Y-m-d H:i', 'd/m/Y H:i', 'd/m/Y'];
        foreach ($formats as $format) {
            $dateObj = DateTime::createFromFormat($format, $date);
            if ($dateObj) {
                return $dateObj->format('Y-m-d H:i:s');
            }
        }

        return null;
    }

    /**
     * Marcar tarefa como concluída
     */
    public function markAsCompleted(): void
    {
        $this->status = self::STATUS_COMPLETED;
        $this->completed_at = date('Y-m-d H:i:s');
    }

    /**
     * Marcar tarefa como pendente
     */
    public function markAsPending(): void
    {
        $this->status = self::STATUS_PENDING;
        $this->completed_at = null;
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
     * Obter número de dias até o vencimento
     */
    public function getDaysUntilDue(): ?int
    {
        if (!$this->due_date) {
            return null;
        }

        $dueDate = new DateTime($this->due_date);
        $now = new DateTime();
        $diff = $now->diff($dueDate);

        return $diff->invert ? -$diff->days : $diff->days;
    }

    /**
     * Verificar se a tarefa vence hoje
     */
    public function isDueToday(): bool
    {
        if (!$this->due_date) {
            return false;
        }

        $dueDate = new DateTime($this->due_date);
        $today = new DateTime();

        return $dueDate->format('Y-m-d') === $today->format('Y-m-d');
    }
}