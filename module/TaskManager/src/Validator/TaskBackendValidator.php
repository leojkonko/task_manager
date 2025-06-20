<?php

declare(strict_types=1);

namespace TaskManager\Validator;

use DateTime;
use InvalidArgumentException;

/**
 * Validador backend para dados de tarefas
 * Garante que todas as validações sejam aplicadas independentemente da origem da requisição
 */
class TaskBackendValidator
{
    /**
     * Valida dados de tarefa antes de criar ou atualizar
     */
    public static function validate(array $data, bool $isCreate = true): array
    {
        $errors = [];

        // Validação específica para criação de tarefas
        if ($isCreate) {
            // Verificar se está tentando criar tarefa durante dias úteis
            $weekdayErrors = self::validateWeekdayCreation();
            if (!empty($weekdayErrors)) {
                $errors['creation_time'] = $weekdayErrors;
            }
        }

        // Validação do título
        $titleErrors = self::validateTitle($data['title'] ?? null, $isCreate);
        if (!empty($titleErrors)) {
            $errors['title'] = $titleErrors;
        }

        // Validação da descrição
        $descriptionErrors = self::validateDescription($data['description'] ?? null);
        if (!empty($descriptionErrors)) {
            $errors['description'] = $descriptionErrors;
        }

        // Validação do status
        $statusErrors = self::validateStatus($data['status'] ?? null);
        if (!empty($statusErrors)) {
            $errors['status'] = $statusErrors;
        }

        // Validação da prioridade
        $priorityErrors = self::validatePriority($data['priority'] ?? null);
        if (!empty($priorityErrors)) {
            $errors['priority'] = $priorityErrors;
        }

        // Validação da data de vencimento
        $dueDateErrors = self::validateDueDate($data['due_date'] ?? null, $isCreate);
        if (!empty($dueDateErrors)) {
            $errors['due_date'] = $dueDateErrors;
        }

        // Validação do user_id
        $userIdErrors = self::validateUserId($data['user_id'] ?? null, $isCreate);
        if (!empty($userIdErrors)) {
            $errors['user_id'] = $userIdErrors;
        }

        // Validação do category_id
        $categoryIdErrors = self::validateCategoryId($data['category_id'] ?? null);
        if (!empty($categoryIdErrors)) {
            $errors['category_id'] = $categoryIdErrors;
        }

        return $errors;
    }

    /**
     * Valida o título da tarefa
     */
    private static function validateTitle(?string $title, bool $isCreate): array
    {
        $errors = [];

        if ($isCreate && (empty($title) || trim($title) === '')) {
            $errors[] = 'O título da tarefa é obrigatório';
            return $errors;
        }

        if ($title !== null) {
            $title = trim($title);

            if (empty($title)) {
                $errors[] = 'O título da tarefa não pode estar vazio';
            } elseif (strlen($title) < 3) {
                $errors[] = 'O título deve ter pelo menos 3 caracteres';
            } elseif (strlen($title) > 200) {
                $errors[] = 'O título não pode ter mais de 200 caracteres';
            } elseif (!preg_match('/^[a-zA-Z0-9\s\-_.,!?áéíóúàèìòùâêîôûãõçÁÉÍÓÚÀÈÌÒÙÂÊÎÔÛÃÕÇ]+$/u', $title)) {
                $errors[] = 'O título contém caracteres inválidos. Use apenas letras, números, espaços e pontuação básica';
            }
        }

        return $errors;
    }

    /**
     * Valida a descrição da tarefa
     */
    private static function validateDescription(?string $description): array
    {
        $errors = [];

        if ($description !== null && strlen($description) > 1000) {
            $errors[] = 'A descrição não pode ter mais de 1000 caracteres';
        }

        return $errors;
    }

    /**
     * Valida o status da tarefa
     */
    private static function validateStatus(?string $status): array
    {
        $errors = [];

        if ($status !== null) {
            $validStatuses = ['pending', 'in_progress', 'completed', 'cancelled'];
            if (!in_array($status, $validStatuses)) {
                $errors[] = 'Status inválido. Valores aceitos: ' . implode(', ', $validStatuses);
            }
        }

        return $errors;
    }

    /**
     * Valida a prioridade da tarefa
     */
    private static function validatePriority(?string $priority): array
    {
        $errors = [];

        if ($priority !== null) {
            $validPriorities = ['low', 'medium', 'high', 'urgent'];
            if (!in_array($priority, $validPriorities)) {
                $errors[] = 'Prioridade inválida. Valores aceitos: ' . implode(', ', $validPriorities);
            }
        }

        return $errors;
    }

    /**
     * Valida a data de vencimento
     */
    private static function validateDueDate(?string $dueDate, bool $isCreate): array
    {
        $errors = [];

        if ($dueDate !== null && !empty($dueDate)) {
            // Verificar se a data é válida
            if (!self::isValidDateTime($dueDate)) {
                $errors[] = 'Data de vencimento inválida. Use o formato Y-m-d H:i:s ou Y-m-d\\TH:i';
                return $errors;
            }

            // Verificar se a data não é no passado (apenas para criação)
            if ($isCreate) {
                try {
                    $dueDateObj = new DateTime($dueDate);
                    $now = new DateTime();
                    if ($dueDateObj < $now) {
                        $errors[] = 'A data de vencimento não pode ser no passado';
                    }
                } catch (\Exception $e) {
                    $errors[] = 'Data de vencimento inválida';
                }
            }
        }

        return $errors;
    }

    /**
     * Valida o ID do usuário
     */
    private static function validateUserId($userId, bool $isCreate): array
    {
        $errors = [];

        if ($isCreate && empty($userId)) {
            $errors[] = 'O ID do usuário é obrigatório';
            return $errors;
        }

        if ($userId !== null) {
            if (!is_numeric($userId) || (int)$userId <= 0) {
                $errors[] = 'ID do usuário deve ser um número positivo';
            }
        }

        return $errors;
    }

    /**
     * Valida o ID da categoria
     */
    private static function validateCategoryId($categoryId): array
    {
        $errors = [];

        if ($categoryId !== null && $categoryId !== '') {
            if (!is_numeric($categoryId) || (int)$categoryId <= 0) {
                $errors[] = 'ID da categoria deve ser um número positivo';
            }
        }

        return $errors;
    }

    /**
     * Verifica se uma string é uma data/hora válida
     */
    private static function isValidDateTime(string $dateTime): bool
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
     * Sanitiza dados de entrada
     */
    public static function sanitize(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            if (is_string($value)) {
                // Remove tags HTML
                $value = strip_tags($value);

                // Remove caracteres de controle
                $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);

                // Trim espaços
                $value = trim($value);
            }

            $sanitized[$key] = $value;
        }

        return $sanitized;
    }

    /**
     * Lança exceção se houver erros de validação
     */
    public static function validateAndThrow(array $data, bool $isCreate = true): void
    {
        $errors = self::validate($data, $isCreate);

        if (!empty($errors)) {
            $errorMessages = [];
            foreach ($errors as $field => $fieldErrors) {
                $errorMessages[] = implode(', ', $fieldErrors);
            }

            throw new InvalidArgumentException('Dados inválidos: ' . implode('; ', $errorMessages));
        }
    }

    /**
     * Valida se a criação da tarefa ocorre em um dia útil
     */
    private static function validateWeekdayCreation(): array
    {
        $errors = [];

        $now = new DateTime();
        $currentDay = (int)$now->format('N'); // Dia da semana (1 = segunda, 7 = domingo)

        // Verifica se o dia atual é sábado (6) ou domingo (7)
        if ($currentDay >= 6) {
            $dayNames = [
                6 => 'Sábado',
                7 => 'Domingo'
            ];

            // Calcular próximo dia útil
            $nextWeekday = clone $now;
            while ((int)$nextWeekday->format('N') >= 6) {
                $nextWeekday->add(new \DateInterval('P1D'));
            }

            $nextWeekdayNames = [
                1 => 'Segunda-feira',
                2 => 'Terça-feira',
                3 => 'Quarta-feira',
                4 => 'Quinta-feira',
                5 => 'Sexta-feira'
            ];

            $nextWeekdayName = $nextWeekdayNames[(int)$nextWeekday->format('N')];
            $nextWeekdayDate = $nextWeekday->format('d/m/Y');

            $errors[] = "📅 Tarefas só podem ser criadas em dias úteis (segunda a sexta-feira). Hoje é {$dayNames[$currentDay]} - tente novamente na {$nextWeekdayName} ({$nextWeekdayDate}).";
        }

        return $errors;
    }

    /**
     * Valida se uma tarefa pode ser atualizada
     * Tarefas só podem ser atualizadas se estiverem com status "pending"
     */
    public static function validateTaskUpdate($currentStatus): array
    {
        $errors = [];

        if ($currentStatus !== 'pending') {
            $statusNames = [
                'in_progress' => 'Em Andamento',
                'completed' => 'Concluída',
                'cancelled' => 'Cancelada'
            ];

            $currentStatusName = $statusNames[$currentStatus] ?? $currentStatus;

            $errors[] = "🔒 Esta tarefa não pode ser editada porque está com status '{$currentStatusName}'. Apenas tarefas 'Pendentes' podem ser modificadas.\n\n💡 Motivo: Tarefas com outros status são protegidas para manter a integridade do histórico do projeto.";
        }

        return $errors;
    }

    /**
     * Valida se uma tarefa pode ser excluída
     * Tarefas só podem ser excluídas se estiverem com status "pending" 
     * e criadas há mais de 5 dias
     */
    public static function validateTaskDeletion($currentStatus, $createdAt = null): array
    {
        $errors = [];

        if ($currentStatus !== 'pending') {
            $statusNames = [
                'in_progress' => 'Em Andamento',
                'completed' => 'Concluída',
                'cancelled' => 'Cancelada'
            ];

            $currentStatusName = $statusNames[$currentStatus] ?? $currentStatus;

            $errors[] = "🔒 Esta tarefa não pode ser excluída porque está com status '{$currentStatusName}'. Apenas tarefas 'Pendentes' podem ser removidas.\n\n🛡️ Proteção: Tarefas que já foram iniciadas, concluídas ou canceladas contêm informações valiosas do histórico do projeto.";
        }

        // Validar se a tarefa foi criada há mais de 5 dias
        if ($createdAt !== null) {
            $creationDateErrors = self::validateDeletionAge($createdAt);
            if (!empty($creationDateErrors)) {
                $errors = array_merge($errors, $creationDateErrors);
            }
        }

        return $errors;
    }

    /**
     * Valida operações de atualização com verificação de status
     */
    public static function validateUpdateOperation($currentTask, array $data): array
    {
        $errors = [];

        // Verificar se a tarefa pode ser atualizada
        $statusErrors = self::validateTaskUpdate($currentTask->getStatus());
        if (!empty($statusErrors)) {
            $errors['operation'] = $statusErrors;
        }

        // Se a tarefa pode ser atualizada, validar os dados
        if (empty($statusErrors)) {
            $dataErrors = self::validate($data, false);
            $errors = array_merge($errors, $dataErrors);
        }

        return $errors;
    }

    /**
     * Valida operações de exclusão com verificação de status e idade
     */
    public static function validateDeleteOperation($currentTask): array
    {
        $errors = [];

        $statusErrors = self::validateTaskDeletion(
            $currentTask->getStatus(),
            $currentTask->getCreatedAt()
        );
        if (!empty($statusErrors)) {
            $errors['operation'] = $statusErrors;
        }

        return $errors;
    }

    /**
     * Valida se a tarefa tem idade suficiente para ser excluída (mais de 5 dias)
     */
    private static function validateDeletionAge($createdAt): array
    {
        $errors = [];

        try {
            // Converter created_at para DateTime se for string
            if (is_string($createdAt)) {
                $createdAtObj = new DateTime($createdAt);
            } elseif ($createdAt instanceof DateTime) {
                $createdAtObj = $createdAt;
            } else {
                $errors[] = 'Data de criação inválida';
                return $errors;
            }

            $now = new DateTime();
            $fiveDaysAgo = (clone $now)->modify('-5 days');

            // Verificar se a tarefa foi criada há mais de 5 dias
            if ($createdAtObj > $fiveDaysAgo) {
                $daysSinceCreation = $now->diff($createdAtObj)->days;
                $remainingDays = 5 - $daysSinceCreation;
                $errors[] = "Tarefas só podem ser excluídas após 5 dias da criação. Aguarde mais {$remainingDays} dia(s)";
            }
        } catch (\Exception $e) {
            $errors[] = 'Erro ao validar data de criação da tarefa';
        }

        return $errors;
    }
}
