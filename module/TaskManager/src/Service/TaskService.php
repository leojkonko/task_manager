<?php

declare(strict_types=1);

namespace TaskManager\Service;

use TaskManager\Entity\Task;
use TaskManager\Repository\TaskRepositoryInterface;
use DateTime;

/**
 * Service para gerenciar a lógica de negócios das tarefas
 */
class TaskService
{
    private TaskRepositoryInterface $taskRepository;

    public function __construct(TaskRepositoryInterface $taskRepository)
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
     * Alias para getTaskById (para compatibilidade com testes)
     */
    public function getTask(int $id): ?Task
    {
        return $this->getTaskById($id);
    }

    /**
     * Busca tarefas de um usuário
     */
    public function getUserTasks(int $userId, array $filters = []): array
    {
        return $this->taskRepository->findByUserId($userId, $filters);
    }

    /**
     * Alias para getUserTasks (para compatibilidade)
     */
    public function getTasks(int $userId, array $filters = []): array
    {
        return $this->getUserTasks($userId, $filters);
    }

    /**
     * Busca tarefas com paginação
     */
    public function getUserTasksWithPagination(int $userId, int $page = 1, int $limit = 10, array $filters = []): array
    {
        return $this->taskRepository->findWithPagination($userId, $page, $limit, $filters);
    }

    /**
     * Alias para getUserTasksWithPagination
     */
    public function getTasksWithPagination(int $userId, int $page = 1, int $limit = 10, array $filters = []): array
    {
        return $this->getUserTasksWithPagination($userId, $page, $limit, $filters);
    }

    /**
     * Busca tarefas por texto
     */
    public function searchTasks(string $searchTerm, int $userId = null): array
    {
        if (strlen($searchTerm) < 3) {
            throw new \InvalidArgumentException('O termo de busca deve ter pelo menos 3 caracteres');
        }

        $filters = ['search' => $searchTerm];
        if ($userId !== null) {
            return $this->taskRepository->findByUserId($userId, $filters);
        }

        return $this->taskRepository->search($searchTerm);
    }

    /**
     * Busca tarefas por prioridade
     */
    public function getTasksByPriority(int $userId, string $priority): array
    {
        return $this->taskRepository->findByPriority($priority, $userId);
    }

    /**
     * Arquiva uma tarefa
     */
    public function archiveTask(int $id): bool
    {
        return $this->taskRepository->archive($id);
    }

    /**
     * Restaura uma tarefa arquivada
     */
    public function restoreTask(int $id): bool
    {
        return $this->taskRepository->restore($id);
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
        return $this->taskRepository->findOverdueTasks(['user_id' => $userId]);
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
        // Validação do título
        if ($isCreate && empty($data['title'])) {
            throw new \InvalidArgumentException('O título da tarefa é obrigatório');
        }

        if (isset($data['title'])) {
            $title = trim($data['title']);
            if (empty($title)) {
                throw new \InvalidArgumentException('O título da tarefa não pode estar vazio');
            }
            if (strlen($title) < 3) {
                throw new \InvalidArgumentException('O título deve ter pelo menos 3 caracteres');
            }
            if (strlen($title) > 200) {
                throw new \InvalidArgumentException('O título não pode ter mais de 200 caracteres');
            }
        }

        // Validação da descrição
        if (isset($data['description']) && $data['description'] !== null) {
            if (strlen($data['description']) > 1000) {
                throw new \InvalidArgumentException('A descrição não pode ter mais de 1000 caracteres');
            }
        }

        // Validação do user_id
        if ($isCreate && empty($data['user_id'])) {
            throw new \InvalidArgumentException('O ID do usuário é obrigatório');
        }

        if (isset($data['user_id'])) {
            if (!is_numeric($data['user_id']) || (int)$data['user_id'] <= 0) {
                throw new \InvalidArgumentException('ID do usuário deve ser um número positivo');
            }
        }

        // Validação do status
        if (isset($data['status'])) {
            $validStatuses = [
                Task::STATUS_PENDING,
                Task::STATUS_IN_PROGRESS,
                Task::STATUS_COMPLETED,
                Task::STATUS_CANCELLED
            ];
            if (!in_array($data['status'], $validStatuses)) {
                throw new \InvalidArgumentException('Status inválido. Valores aceitos: ' . implode(', ', $validStatuses));
            }
        }

        // Validação da prioridade
        if (isset($data['priority'])) {
            $validPriorities = [
                Task::PRIORITY_LOW,
                Task::PRIORITY_MEDIUM,
                Task::PRIORITY_HIGH,
                Task::PRIORITY_URGENT
            ];
            if (!in_array($data['priority'], $validPriorities)) {
                throw new \InvalidArgumentException('Prioridade inválida. Valores aceitos: ' . implode(', ', $validPriorities));
            }
        }

        // Validação da data de vencimento
        if (isset($data['due_date']) && !empty($data['due_date'])) {
            if (!$this->isValidDateTime($data['due_date'])) {
                throw new \InvalidArgumentException('Data de vencimento inválida. Use o formato Y-m-d H:i:s ou Y-m-d\\TH:i');
            }

            // Verificar se a data não é no passado (apenas para criação)
            if ($isCreate) {
                $dueDate = new DateTime($data['due_date']);
                $now = new DateTime();
                if ($dueDate < $now) {
                    throw new \InvalidArgumentException('A data de vencimento não pode ser no passado');
                }
            }
        }

        // Validação do category_id
        if (isset($data['category_id']) && $data['category_id'] !== null) {
            if (!is_numeric($data['category_id']) || (int)$data['category_id'] <= 0) {
                throw new \InvalidArgumentException('ID da categoria deve ser um número positivo');
            }
        }
    }

    /**
     * Verifica se uma string é uma data/hora válida
     */
    private function isValidDateTime(string $dateTime): bool
    {
        // Formato ISO 8601 (Y-m-d\TH:i)
        if (DateTime::createFromFormat('Y-m-d\TH:i', $dateTime) !== false) {
            return true;
        }

        // Formato MySQL (Y-m-d H:i:s)
        if (DateTime::createFromFormat('Y-m-d H:i:s', $dateTime) !== false) {
            return true;
        }

        // Formato apenas data (Y-m-d)
        if (DateTime::createFromFormat('Y-m-d', $dateTime) !== false) {
            return true;
        }

        return false;
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

    /**
     * Exclui uma tarefa
     */
    public function deleteTask(int $id): bool
    {
        return $this->taskRepository->delete($id);
    }

    /**
     * Operações em lote - deletar múltiplas tarefas
     */
    public function bulkDeleteTasks(array $taskIds): int
    {
        $deletedCount = 0;
        foreach ($taskIds as $id) {
            if ($this->deleteTask($id)) {
                $deletedCount++;
            }
        }
        return $deletedCount;
    }

    /**
     * Operações em lote - atualizar múltiplas tarefas
     */
    public function bulkUpdateTasks(array $taskIds, array $updateData): int
    {
        $updatedCount = 0;
        foreach ($taskIds as $id) {
            if ($this->updateTask($id, $updateData)) {
                $updatedCount++;
            }
        }
        return $updatedCount;
    }

    /**
     * Cria uma nova tarefa (versão que retorna array para compatibilidade com testes)
     */
    public function createTaskWithResult(array $data): array
    {
        try {
            $task = $this->createTask($data);
            return [
                'success' => true,
                'id' => $task->getId(),
                'message' => 'Tarefa criada com sucesso',
                'task' => $task
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $this->parseValidationErrors($e->getMessage())
            ];
        }
    }

    /**
     * Atualiza uma tarefa (versão que retorna array para compatibilidade com testes)
     */
    public function updateTaskWithResult(int $id, array $data, int $userId = null): array
    {
        try {
            $task = $this->updateTask($id, $data);
            if ($task) {
                return [
                    'success' => true,
                    'message' => 'Tarefa atualizada com sucesso',
                    'task' => $task
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Tarefa não encontrada'
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $this->parseValidationErrors($e->getMessage())
            ];
        }
    }

    /**
     * Exclui uma tarefa (versão que retorna array para compatibilidade com testes)
     */
    public function deleteTaskWithResult(int $id, int $userId = null): array
    {
        try {
            $success = $this->deleteTask($id);
            if ($success) {
                return [
                    'success' => true,
                    'message' => 'Tarefa excluída com sucesso'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Tarefa não encontrada'
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Marca tarefa como concluída (versão que retorna array para testes)
     */
    public function completeTaskWithResult(int $id, int $userId = null): array
    {
        try {
            $task = $this->completeTask($id);
            if ($task) {
                return [
                    'success' => true,
                    'message' => 'Tarefa marcada como concluída',
                    'task' => $task
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Tarefa não encontrada'
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Duplica uma tarefa (versão que retorna array para testes)
     */
    public function duplicateTaskWithResult(int $id, int $userId = null): array
    {
        try {
            $task = $this->duplicateTask($id);
            if ($task) {
                return [
                    'success' => true,
                    'new_task_id' => $task->getId(),
                    'message' => 'Tarefa duplicada com sucesso',
                    'task' => $task
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Tarefa não encontrada'
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Busca tarefas com paginação (versão que retorna array estruturado)
     */
    public function getTasksWithResult(array $filters, int $page, int $limit, int $userId): array
    {
        try {
            $result = $this->getUserTasksWithPagination($userId, $page, $limit, $filters);
            return [
                'success' => true,
                'tasks' => $result['tasks'],
                'pagination' => $result
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'tasks' => [],
                'pagination' => ['page' => $page, 'limit' => $limit, 'total' => 0, 'total_pages' => 0]
            ];
        }
    }

    /**
     * Parse validation errors from exception message
     */
    private function parseValidationErrors(string $message): array
    {
        $errors = [];
        
        if (strpos($message, 'título') !== false) {
            $errors['title'] = [$message];
        }
        if (strpos($message, 'descrição') !== false) {
            $errors['description'] = [$message];
        }
        if (strpos($message, 'prioridade') !== false) {
            $errors['priority'] = [$message];
        }
        if (strpos($message, 'usuário') !== false) {
            $errors['user_id'] = [$message];
        }
        
        return $errors ?: ['general' => [$message]];
    }
}
