<?php

namespace TaskManager\Model;

use DomainException;
use InvalidArgumentException;
use DateTime;

class Task
{
    // Constants for valid statuses
    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    // Constants for valid priorities
    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';

    // Task properties
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
     * List of valid statuses
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
     * List of valid priorities
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
     * Exchange array data with validation
     */
    public function exchangeArray(array $data)
    {
        $this->id = !empty($data['id']) ? (int)$data['id'] : null;

        // Validate and set title
        $this->setTitle($data['title'] ?? '');

        // Set description
        $this->description = !empty($data['description']) ? trim($data['description']) : null;

        // Validate and set status
        $this->setStatus($data['status'] ?? self::STATUS_PENDING);

        // Validate and set priority
        $this->setPriority($data['priority'] ?? self::PRIORITY_MEDIUM);

        // Validate and set due date
        $this->setDueDate($data['due_date'] ?? null);

        $this->completed_at = !empty($data['completed_at']) ? $data['completed_at'] : null;
        $this->user_id = !empty($data['user_id']) ? (int)$data['user_id'] : null;
        $this->category_id = !empty($data['category_id']) ? (int)$data['category_id'] : null;
        $this->created_at = !empty($data['created_at']) ? $data['created_at'] : null;
        $this->updated_at = !empty($data['updated_at']) ? $data['updated_at'] : null;
    }

    /**
     * Validate and set task title
     */
    public function setTitle($title)
    {
        $title = trim($title);

        if (empty($title)) {
            throw new InvalidArgumentException('Task title cannot be empty.');
        }

        if (strlen($title) > 200) {
            throw new InvalidArgumentException('Task title cannot exceed 200 characters.');
        }

        if (strlen($title) < 3) {
            throw new InvalidArgumentException('Task title must be at least 3 characters long.');
        }

        $this->title = $title;
    }

    /**
     * Validate and set task status
     */
    public function setStatus($status)
    {
        if (!in_array($status, self::getValidStatuses())) {
            throw new InvalidArgumentException(sprintf(
                'Status "%s" is invalid. Valid statuses are: %s',
                $status,
                implode(', ', self::getValidStatuses())
            ));
        }

        $this->status = $status;

        // Automatically set completed_at when status is completed
        if ($status === self::STATUS_COMPLETED && !$this->completed_at) {
            $this->completed_at = date('Y-m-d H:i:s');
        } elseif ($status !== self::STATUS_COMPLETED) {
            $this->completed_at = null;
        }
    }

    /**
     * Validate and set task priority
     */
    public function setPriority($priority)
    {
        if (!in_array($priority, self::getValidPriorities())) {
            throw new InvalidArgumentException(sprintf(
                'Priority "%s" is invalid. Valid priorities are: %s',
                $priority,
                implode(', ', self::getValidPriorities())
            ));
        }

        $this->priority = $priority;
    }

    /**
     * Validate and set due date
     */
    public function setDueDate($dueDate)
    {
        if (empty($dueDate)) {
            $this->due_date = null;
            return;
        }

        // Validar formato de data
        if (!$this->isValidDateTime($dueDate)) {
            throw new InvalidArgumentException('Due date must be in a valid format.');
        }

        $this->due_date = $dueDate;
    }

    /**
     * Check if a string is a valid date/time
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
     * Validate task data
     */
    public function validate()
    {
        $errors = [];

        if (empty($this->title)) {
            $errors[] = 'Task title is required.';
        }

        if (empty($this->user_id)) {
            $errors[] = 'Task user is required.';
        }

        if (!empty($errors)) {
            throw new DomainException('Task data is invalid: ' . implode(' ', $errors));
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
            self::STATUS_PENDING => 'Pending',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled'
        ];

        return isset($labels[$this->status]) ? $labels[$this->status] : 'Unknown';
    }

    public function getPriorityLabel()
    {
        $labels = [
            self::PRIORITY_LOW => 'Low',
            self::PRIORITY_MEDIUM => 'Medium',
            self::PRIORITY_HIGH => 'High',
            self::PRIORITY_URGENT => 'Urgent'
        ];

        return isset($labels[$this->priority]) ? $labels[$this->priority] : 'Medium';
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
     * Check if task is completed
     */
    public function isCompleted()
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if task is in progress
     */
    public function isInProgress()
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    /**
     * Check if task is pending
     */
    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Get number of days until due
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
     * Mark task as completed
     */
    public function markAsCompleted()
    {
        $this->setStatus(self::STATUS_COMPLETED);
    }

    /**
     * Mark task as in progress
     */
    public function markAsInProgress()
    {
        $this->setStatus(self::STATUS_IN_PROGRESS);
    }
}
