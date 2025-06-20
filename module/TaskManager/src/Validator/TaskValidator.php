<?php

declare(strict_types=1);

namespace TaskManager\Validator;

use Laminas\Validator\AbstractValidator;
use DateTime;

/**
 * Validator customizado para validar dados de tarefas
 */
class TaskValidator extends AbstractValidator
{
    const TITLE_TOO_SHORT = 'titleTooShort';
    const TITLE_TOO_LONG = 'titleTooLong';
    const TITLE_EMPTY = 'titleEmpty';
    const DESCRIPTION_TOO_LONG = 'descriptionTooLong';
    const DUE_DATE_IN_PAST = 'dueDateInPast';
    const DUE_DATE_INVALID = 'dueDateInvalid';

    protected $messageTemplates = [
        self::TITLE_TOO_SHORT => 'O título deve ter pelo menos 3 caracteres',
        self::TITLE_TOO_LONG => 'O título não pode ter mais de 200 caracteres',
        self::TITLE_EMPTY => 'O título da tarefa não pode estar vazio',
        self::DESCRIPTION_TOO_LONG => 'A descrição não pode ter mais de 1000 caracteres',
        self::DUE_DATE_IN_PAST => 'A data de vencimento não pode ser no passado',
        self::DUE_DATE_INVALID => 'Data de vencimento inválida',
    ];

    public function isValid($value, $context = null)
    {
        $this->setValue($value);
        $isValid = true;

        // Validação do título
        if (isset($context['title'])) {
            $title = trim($context['title']);

            if (empty($title)) {
                $this->error(self::TITLE_EMPTY);
                $isValid = false;
            } elseif (strlen($title) < 3) {
                $this->error(self::TITLE_TOO_SHORT);
                $isValid = false;
            } elseif (strlen($title) > 200) {
                $this->error(self::TITLE_TOO_LONG);
                $isValid = false;
            }
        }

        // Validação da descrição
        if (isset($context['description']) && !empty($context['description'])) {
            if (strlen($context['description']) > 1000) {
                $this->error(self::DESCRIPTION_TOO_LONG);
                $isValid = false;
            }
        }

        // Validação da data de vencimento
        if (isset($context['due_date']) && !empty($context['due_date'])) {
            try {
                $dueDate = new DateTime($context['due_date']);
                $now = new DateTime();

                if ($dueDate < $now) {
                    $this->error(self::DUE_DATE_IN_PAST);
                    $isValid = false;
                }
            } catch (\Exception $e) {
                $this->error(self::DUE_DATE_INVALID);
                $isValid = false;
            }
        }

        return $isValid;
    }
}
