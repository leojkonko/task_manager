<?php

declare(strict_types=1);

namespace TaskManager\Entity;

use DateTime;

/**
 * Classe que representa uma tarefa no sistema
 */
class Task
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const PRIORITY_LOW = 'low';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_URGENT = 'urgent';

    private ?int $id = null;
    private string $title;
    private ?string $description = null;
    private string $status = self::STATUS_PENDING;
    private string $priority = self::PRIORITY_MEDIUM;
    private ?DateTime $dueDate = null;
    private ?DateTime $completedAt = null;
    private int $userId;
    private ?int $categoryId = null;
    private ?DateTime $createdAt = null;
    private ?DateTime $updatedAt = null;
    private $user = null; // User object for notifications

    public function __construct()
    {
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $title = trim($title);

        if (empty($title)) {
            throw new \InvalidArgumentException('O título da tarefa não pode estar vazio');
        }

        if (strlen($title) < 3) {
            throw new \InvalidArgumentException('O título deve ter pelo menos 3 caracteres');
        }

        if (strlen($title) > 200) {
            throw new \InvalidArgumentException('O título não pode ter mais de 200 caracteres');
        }

        $this->title = $title;
        $this->updateTimestamp();
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        if ($description !== null) {
            $description = trim($description);

            if (strlen($description) > 1000) {
                throw new \InvalidArgumentException('A descrição não pode ter mais de 1000 caracteres');
            }

            // Se a descrição estiver vazia após o trim, definir como null
            if (empty($description)) {
                $description = null;
            }
        }

        $this->description = $description;
        $this->updateTimestamp();
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        if (!in_array($status, [
            self::STATUS_PENDING,
            self::STATUS_IN_PROGRESS,
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED
        ])) {
            throw new \InvalidArgumentException('Status inválido: ' . $status);
        }

        $this->status = $status;

        // Se a tarefa for marcada como concluída, definir a data de conclusão
        if ($status === self::STATUS_COMPLETED && $this->completedAt === null) {
            $this->completedAt = new DateTime();
        } elseif ($status !== self::STATUS_COMPLETED) {
            $this->completedAt = null;
        }

        $this->updateTimestamp();
        return $this;
    }

    public function getPriority(): string
    {
        return $this->priority;
    }

    public function setPriority(string $priority): self
    {
        if (!in_array($priority, [
            self::PRIORITY_LOW,
            self::PRIORITY_MEDIUM,
            self::PRIORITY_HIGH,
            self::PRIORITY_URGENT
        ])) {
            throw new \InvalidArgumentException('Prioridade inválida: ' . $priority);
        }

        $this->priority = $priority;
        $this->updateTimestamp();
        return $this;
    }

    public function getDueDate(): ?DateTime
    {
        return $this->dueDate;
    }

    public function setDueDate(?DateTime $dueDate): self
    {
        $this->dueDate = $dueDate;
        $this->updateTimestamp();
        return $this;
    }

    public function getCompletedAt(): ?DateTime
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?DateTime $completedAt): self
    {
        $this->completedAt = $completedAt;
        return $this;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function getCategoryId(): ?int
    {
        return $this->categoryId;
    }

    public function setCategoryId(?int $categoryId): self
    {
        $this->categoryId = $categoryId;
        $this->updateTimestamp();
        return $this;
    }

    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * Get user object (for notifications)
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set user object (for notifications)
     */
    public function setUser($user): self
    {
        $this->user = $user;
        return $this;
    }

    /**
     * Verifica se a tarefa está concluída
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Verifica se a tarefa está em andamento
     */
    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    /**
     * Verifica se a tarefa está pendente
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Verifica se a tarefa está atrasada
     */
    public function isOverdue(): bool
    {
        if ($this->dueDate === null || $this->isCompleted()) {
            return false;
        }

        return $this->dueDate < new DateTime();
    }

    /**
     * Retorna um array com os dados da tarefa
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'priority' => $this->priority,
            'due_date' => $this->dueDate?->format('Y-m-d H:i:s'),
            'completed_at' => $this->completedAt?->format('Y-m-d H:i:s'),
            'user_id' => $this->userId,
            'category_id' => $this->categoryId,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'is_completed' => $this->isCompleted(),
            'is_in_progress' => $this->isInProgress(),
            'is_pending' => $this->isPending(),
            'is_overdue' => $this->isOverdue(),
        ];
    }

    /**
     * Atualiza o timestamp de modificação
     */
    private function updateTimestamp(): void
    {
        $this->updatedAt = new DateTime();
    }

    /**
     * Retorna os status disponíveis
     */
    public static function getAvailableStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pendente',
            self::STATUS_IN_PROGRESS => 'Em Andamento',
            self::STATUS_COMPLETED => 'Concluída',
            self::STATUS_CANCELLED => 'Cancelada',
        ];
    }

    /**
     * Retorna as prioridades disponíveis
     */
    public static function getAvailablePriorities(): array
    {
        return [
            self::PRIORITY_LOW => 'Baixa',
            self::PRIORITY_MEDIUM => 'Média',
            self::PRIORITY_HIGH => 'Alta',
            self::PRIORITY_URGENT => 'Urgente',
        ];
    }
}
