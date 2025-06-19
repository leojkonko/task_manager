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
 * Formulário para criar e editar tarefas
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
     * Adiciona elementos ao formulário
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
                'label' => 'Título da Tarefa',
            ],
            'attributes' => [
                'id' => 'title',
                'class' => 'form-control',
                'placeholder' => 'Digite o título da tarefa...',
                'required' => true,
                'maxlength' => 200,
            ],
        ]);

        // Descrição
        $this->add([
            'type' => Element\Textarea::class,
            'name' => 'description',
            'options' => [
                'label' => 'Descrição',
            ],
            'attributes' => [
                'id' => 'description',
                'class' => 'form-control',
                'placeholder' => 'Descreva os detalhes da tarefa...',
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
                    Task::STATUS_PENDING => 'Pendente',
                    Task::STATUS_IN_PROGRESS => 'Em Andamento',
                    Task::STATUS_COMPLETED => 'Concluída',
                    Task::STATUS_CANCELLED => 'Cancelada',
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
                'label' => 'Prioridade',
                'value_options' => [
                    Task::PRIORITY_LOW => 'Baixa',
                    Task::PRIORITY_MEDIUM => 'Média',
                    Task::PRIORITY_HIGH => 'Alta',
                    Task::PRIORITY_URGENT => 'Urgente',
                ],
            ],
            'attributes' => [
                'id' => 'priority',
                'class' => 'form-select',
            ],
        ]);

        // Data de Vencimento
        $this->add([
            'type' => Element\DateTime::class,
            'name' => 'due_date',
            'options' => [
                'label' => 'Data de Vencimento',
                'format' => 'Y-m-d\TH:i',
            ],
            'attributes' => [
                'id' => 'due_date',
                'class' => 'form-control',
                'step' => '1',
            ],
        ]);

        // Categoria (será implementada posteriormente)
        $this->add([
            'type' => Element\Select::class,
            'name' => 'category_id',
            'options' => [
                'label' => 'Categoria',
                'value_options' => [
                    '' => 'Selecione uma categoria...',
                    1 => 'Trabalho',
                    2 => 'Pessoal',
                    3 => 'Urgente',
                    4 => 'Estudos',
                ],
                'empty_option' => 'Nenhuma categoria',
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
                'value' => 'Salvar Tarefa',
                'class' => 'btn btn-primary',
                'id' => 'submit-button',
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

        // Título
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
                            Validator\NotEmpty::IS_EMPTY => 'O título da tarefa é obrigatório',
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
                            Validator\StringLength::TOO_SHORT => 'O título deve ter pelo menos %min% caracteres',
                            Validator\StringLength::TOO_LONG => 'O título deve ter no máximo %max% caracteres',
                        ],
                    ],
                ],
            ],
        ]);

        // Descrição
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
                            Validator\StringLength::TOO_LONG => 'A descrição deve ter no máximo %max% caracteres',
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
                            Validator\InArray::NOT_IN_ARRAY => 'Status inválido selecionado',
                        ],
                    ],
                ],
            ],
        ]);

        // Prioridade
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
                            Validator\InArray::NOT_IN_ARRAY => 'Prioridade inválida selecionada',
                        ],
                    ],
                ],
            ],
        ]);

        // Data de Vencimento
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
                            Validator\Date::INVALID_DATE => 'Data de vencimento inválida',
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
