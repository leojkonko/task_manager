<?php

declare(strict_types=1);

namespace TaskManager\Form;

use Laminas\Form\Form;
use Laminas\Form\Element;
use Laminas\InputFilter\InputFilter;
use Laminas\InputFilter\Input;
use Laminas\Validator;
use Laminas\Filter;
use TaskManager\Entity\Task;

/**
 * Form for creating and editing tasks
 */
class TaskForm extends Form
{
    public function __construct($name = null)
    {
        parent::__construct('task-form');

        $this->setAttribute('method', 'post');
        $this->setAttribute('class', 'form-horizontal');

        $this->addElements();
        $this->setInputFilter($this->createInputFilter());
    }

    /**
     * Add elements to the form
     */
    protected function addElements(): void
    {
        // ID (hidden)
        $this->add([
            'type' => Element\Hidden::class,
            'name' => 'id',
        ]);

        // Título
        $this->add([
            'type' => Element\Text::class,
            'name' => 'title',
            'options' => [
                'label' => 'Task Title',
            ],
            'attributes' => [
                'id' => 'title',
                'class' => 'form-control',
                'placeholder' => 'Enter task title...',
                'required' => true,
                'maxlength' => 200,
            ],
        ]);

        // Descrição
        $this->add([
            'type' => Element\Textarea::class,
            'name' => 'description',
            'options' => [
                'label' => 'Description',
            ],
            'attributes' => [
                'id' => 'description',
                'class' => 'form-control',
                'placeholder' => 'Describe the task details...',
                'rows' => 4,
            ],
        ]);

        // Status
        $this->add([
            'type' => Element\Select::class,
            'name' => 'status',
            'options' => [
                'label' => 'Status',
                'value_options' => [
                    Task::STATUS_PENDING => 'Pending',
                    Task::STATUS_IN_PROGRESS => 'In Progress',
                    Task::STATUS_COMPLETED => 'Completed',
                    Task::STATUS_CANCELLED => 'Cancelled',
                ],
            ],
            'attributes' => [
                'id' => 'status',
                'class' => 'form-select',
            ],
        ]);

        // Prioridade
        $this->add([
            'type' => Element\Select::class,
            'name' => 'priority',
            'options' => [
                'label' => 'Priority',
                'value_options' => [
                    Task::PRIORITY_LOW => 'Low',
                    Task::PRIORITY_MEDIUM => 'Medium',
                    Task::PRIORITY_HIGH => 'High',
                    Task::PRIORITY_URGENT => 'Urgent',
                ],
            ],
            'attributes' => [
                'id' => 'priority',
                'class' => 'form-select',
            ],
        ]);

        // Data de vencimento
        $this->add([
            'type' => Element\DateTime::class,
            'name' => 'due_date',
            'options' => [
                'label' => 'Due Date',
            ],
            'attributes' => [
                'id' => 'due_date',
                'class' => 'form-control',
                'step' => '1',
            ],
        ]);

        // Categoria
        $this->add([
            'type' => Element\Select::class,
            'name' => 'category_id',
            'options' => [
                'label' => 'Category',
                'value_options' => $this->getCategoryOptions(),
                'empty_option' => 'Select a category...',
            ],
            'attributes' => [
                'id' => 'category_id',
                'class' => 'form-select',
            ],
        ]);

        // CSRF Token
        $this->add([
            'type' => Element\Csrf::class,
            'name' => 'csrf',
            'options' => [
                'csrf_options' => [
                    'timeout' => 600,
                ],
            ],
        ]);

        // Submit Button
        $this->add([
            'type' => Element\Submit::class,
            'name' => 'submit',
            'attributes' => [
                'value' => 'Save Task',
                'class' => 'btn btn-primary',
            ],
        ]);
    }

    /**
     * Cria filtros e validadores para o formulário
     */
    protected function createInputFilter(): InputFilter
    {
        $inputFilter = new InputFilter();

        // ID
        $inputFilter->add([
            'name' => 'id',
            'required' => false,
            'filters' => [
                ['name' => Filter\ToInt::class],
            ],
        ]);

        // Title
        $inputFilter->add([
            'name' => 'title',
            'required' => true,
            'filters' => [
                ['name' => Filter\StringTrim::class],
                ['name' => Filter\StripTags::class],
            ],
            'validators' => [
                [
                    'name' => Validator\NotEmpty::class,
                    'options' => [
                        'messages' => [
                            Validator\NotEmpty::IS_EMPTY => 'Task title is required',
                        ],
                    ],
                ],
                [
                    'name' => Validator\StringLength::class,
                    'options' => [
                        'encoding' => 'UTF-8',
                        'min' => 3,
                        'max' => 200,
                        'messages' => [
                            Validator\StringLength::TOO_SHORT => 'Title must be at least 3 characters long',
                            Validator\StringLength::TOO_LONG => 'Title cannot exceed 200 characters',
                        ],
                    ],
                ],
                [
                    'name' => Validator\Regex::class,
                    'options' => [
                        'pattern' => '/^[a-zA-Z0-9\s\-_.,!?áéíóúàèìòùâêîôûãõçÁÉÍÓÚÀÈÌÒÙÂÊÎÔÛÃÕÇ]+$/u',
                        'messages' => [
                            Validator\Regex::NOT_MATCH => 'Title contains invalid characters',
                        ],
                    ],
                ],
            ],
        ]);

        // Description
        $inputFilter->add([
            'name' => 'description',
            'required' => false,
            'filters' => [
                ['name' => Filter\StringTrim::class],
                ['name' => Filter\StripTags::class],
            ],
            'validators' => [
                [
                    'name' => Validator\StringLength::class,
                    'options' => [
                        'encoding' => 'UTF-8',
                        'max' => 1000,
                        'messages' => [
                            Validator\StringLength::TOO_LONG => 'Description must not exceed %max% characters',
                        ],
                    ],
                ],
            ],
        ]);

        // Status
        $inputFilter->add([
            'name' => 'status',
            'required' => true,
            'filters' => [
                ['name' => Filter\StringTrim::class],
            ],
            'validators' => [
                [
                    'name' => Validator\InArray::class,
                    'options' => [
                        'haystack' => [
                            Task::STATUS_PENDING,
                            Task::STATUS_IN_PROGRESS,
                            Task::STATUS_COMPLETED,
                            Task::STATUS_CANCELLED,
                        ],
                        'messages' => [
                            Validator\InArray::NOT_IN_ARRAY => 'Invalid status selected',
                        ],
                    ],
                ],
            ],
        ]);

        // Priority
        $inputFilter->add([
            'name' => 'priority',
            'required' => true,
            'filters' => [
                ['name' => Filter\StringTrim::class],
            ],
            'validators' => [
                [
                    'name' => Validator\InArray::class,
                    'options' => [
                        'haystack' => [
                            Task::PRIORITY_LOW,
                            Task::PRIORITY_MEDIUM,
                            Task::PRIORITY_HIGH,
                            Task::PRIORITY_URGENT,
                        ],
                        'messages' => [
                            Validator\InArray::NOT_IN_ARRAY => 'Invalid priority selected',
                        ],
                    ],
                ],
            ],
        ]);

        // Due Date
        $inputFilter->add([
            'name' => 'due_date',
            'required' => false,
            'filters' => [
                ['name' => Filter\StringTrim::class],
            ],
            'validators' => [
                [
                    'name' => Validator\Date::class,
                    'options' => [
                        'format' => 'Y-m-d\TH:i',
                        'messages' => [
                            Validator\Date::INVALID_DATE => 'Invalid due date',
                        ],
                    ],
                ],
            ],
        ]);

        // Categoria
        $inputFilter->add([
            'name' => 'category_id',
            'required' => false,
            'filters' => [
                ['name' => Filter\ToNull::class],
                ['name' => Filter\ToInt::class],
            ],
        ]);

        return $inputFilter;
    }

    /**
     * Popula o formulário com dados de uma tarefa
     */
    public function populateFromTask(Task $task): void
    {
        $data = [
            'id' => $task->getId(),
            'title' => $task->getTitle(),
            'description' => $task->getDescription(),
            'status' => $task->getStatus(),
            'priority' => $task->getPriority(),
            'category_id' => $task->getCategoryId(),
        ];

        if ($task->getDueDate()) {
            $data['due_date'] = $task->getDueDate()->format('Y-m-d\TH:i');
        }

        $this->setData($data);
    }

    /**
     * Retorna dados formatados para criar/atualizar uma tarefa
     */
    public function getTaskData(): array
    {
        $data = $this->getData();

        // Converter data se fornecida
        if (!empty($data['due_date'])) {
            $data['due_date'] = $data['due_date'];
        } else {
            unset($data['due_date']);
        }

        // Remover campos que não devem ser enviados
        unset($data['csrf'], $data['submit']);

        return $data;
    }
}
