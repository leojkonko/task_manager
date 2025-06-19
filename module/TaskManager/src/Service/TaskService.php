<?php

declare(strict_types=1);

namespace TaskManager\Service;

use TaskManager\Entity\Task;
use TaskManager\Repository\TaskRepository;
use DateTime;

/**
 * Service para gerenciar a lógica de negócios das tarefas
 */
class TaskService
{
    private TaskRepository $taskRepository;

    public function __construct(TaskRepository $taskRepository)
    {
        $this->taskRepository = $taskRepository;
    }

    /**
     * Cria uma nova tarefa
     */
    public function createTask(array $data): Task
    {
        $this->validateTaskData($data);

        $task = new Task();
        $task->setTitle($data['title']);
        $task->setDescription($data['description'] ?? null);
        $task->setUserId($data['user_id']);
        
        if (isset($data['status'])) {
            $task->setStatus($data['status']);
        }
        
        if (isset($data['priority'])) {
            $task->setPriority($data['priority']);
        }
        
        if (isset($data['category_id'])) {
            $task->setCategoryId($data['category_id']);
        }
        
        if (isset($data['due_date']) && !empty($data['due_date'])) {
            $task->setDueDate(new DateTime($data['due_date']));
        }

        return $this->taskRepository->save($task);
    }

    /**
     * Atualiza uma tarefa existente
     */
    public function updateTask(int $id, array $data): ?Task
    {
        $task = $this->taskRepository->findById($id);
        
        if (!$task) {
            return null;
        }

        $this->validateTaskData($data, false);

        if (isset($data['title'])) {
            $task->setTitle($data['title']);
        }
        
        if (isset($data['description'])) {
            $task->setDescription($data['description']);
        }
        
        if (isset($data['status'])) {
            $task->setStatus($data['status']);
        }
        
        if (isset($data['priority'])) {
            $task->setPriority($data['priority']);
        }
        
        if (isset($data['category_id'])) {
            $task->setCategoryId($data['category_id']);
        }
        
        if (isset($data['due_date'])) {
            if (!empty($data['due_date'])) {
                $task->setDueDate(new DateTime($data['due_date']));
            } else {
                $task->setDueDate(null);
            }
        }

        return $this->taskRepository->save($task);
    }

    /**
     * Busca uma tarefa pelo ID
     */
    public function getTaskById(int $id): ?Task
    {
        return $this->taskRepository->findById($id);
    }

    /**
     * Busca tarefas de um usuário
     */
    public function getUserTasks(int $userId, array $filters = []): array
    {
        return $this->taskRepository->findByUserId($userId, $filters);
    }

    /**
     * Busca tarefas com paginação
     */
    public function getUserTasksWithPagination(int $userId, int $page = 1, int $limit = 10, array $filters = []): array
    {
        return $this->taskRepository->findWithPagination($userId, $page, $limit, $filters);
    }

    /**
     * Exclui uma tarefa
     */
    public function deleteTask(int $id): bool
    {
        return $this->taskRepository->delete($id);
    }

    /**
     * Marca uma tarefa como concluída
     */
    public function completeTask(int $id): ?Task
    {
        $task = $this->taskRepository->findById($id);
        
        if (!$task) {
            return null;
        }

        $task->setStatus(Task::STATUS_COMPLETED);
        return $this->taskRepository->save($task);
    }

    /**
     * Marca uma tarefa como em andamento
     */
    public function startTask(int $id): ?Task
    {
        $task = $this->taskRepository->findById($id);
        
        if (!$task) {
            return null;
        }

        $task->setStatus(Task::STATUS_IN_PROGRESS);
        return $this->taskRepository->save($task);
    }

    /**
     * Busca tarefas vencidas de um usuário
     */
    public function getOverdueTasks(int $userId): array
    {
        return $this->taskRepository->findOverdueTasks($userId);
    }

    /**
     * Busca estatísticas das tarefas de um usuário
     */
    public function getTaskStatistics(int $userId): array
    {
        return $this->taskRepository->getStatistics($userId);
    }

    /**
     * Valida os dados da tarefa
     */
    private function validateTaskData(array $data, bool $isCreate = true): void
    {
        if ($isCreate && empty($data['title'])) {
            throw new \InvalidArgumentException('O título da tarefa é obrigatório');
        }

        if (isset($data['title']) && empty(trim($data['title']))) {
            throw new \InvalidArgumentException('O título da tarefa não pode estar vazio');
        }

        if ($isCreate && empty($data['user_id'])) {
            throw new \InvalidArgumentException('O ID do usuário é obrigatório');
        }

        if (isset($data['status']) && !in_array($data['status'], [
            Task::STATUS_PENDING,
            Task::STATUS_IN_PROGRESS,
            Task::STATUS_COMPLETED,
            Task::STATUS_CANCELLED
        ])) {
            throw new \InvalidArgumentException('Status inválido');
        }

        if (isset($data['priority']) && !in_array($data['priority'], [
            Task::PRIORITY_LOW,
            Task::PRIORITY_MEDIUM,
            Task::PRIORITY_HIGH,
            Task::PRIORITY_URGENT
        ])) {
            throw new \InvalidArgumentException('Prioridade inválida');
        }

        if (isset($data['due_date']) && !empty($data['due_date'])) {
            try {
                new DateTime($data['due_date']);
            } catch (\Exception $e) {
                throw new \InvalidArgumentException('Data de vencimento inválida');
            }
        }
    }

    /**
     * Duplica uma tarefa
     */
    public function duplicateTask(int $id): ?Task
    {
        $originalTask = $this->taskRepository->findById($id);
        
        if (!$originalTask) {
            return null;
        }

        $newTask = new Task();
        $newTask->setTitle($originalTask->getTitle() . ' (Cópia)');
        $newTask->setDescription($originalTask->getDescription());
        $newTask->setStatus(Task::STATUS_PENDING);
        $newTask->setPriority($originalTask->getPriority());
        $newTask->setUserId($originalTask->getUserId());
        $newTask->setCategoryId($originalTask->getCategoryId());
        $newTask->setDueDate($originalTask->getDueDate());

        return $this->taskRepository->save($newTask);
    }

    /**
     * Altera a prioridade de uma tarefa
     */
    public function changeTaskPriority(int $id, string $priority): ?Task
    {
        $task = $this->taskRepository->findById($id);
        
        if (!$task) {
            return null;
        }

        $task->setPriority($priority);
        return $this->taskRepository->save($task);
    }

    /**
     * Move uma tarefa para uma categoria
     */
    public function moveTaskToCategory(int $id, ?int $categoryId): ?Task
    {
        $task = $this->taskRepository->findById($id);
        
        if (!$task) {
            return null;
        }

        $task->setCategoryId($categoryId);
        return $this->taskRepository->save($task);
    }
}