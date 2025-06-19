<?php

namespace TaskManager\Form;

use Laminas\Form\Form;
use Laminas\InputFilter\InputFilter;
use Laminas\InputFilter\InputFilterAwareInterface;
use Laminas\InputFilter\InputFilterInterface;
use Laminas\Validator\StringLength;
use Laminas\Validator\NotEmpty;
use Laminas\Validator\InArray;
use Laminas\Validator\Date;
use TaskManager\Model\Task;

class TaskForm extends Form implements InputFilterAwareInterface
{
    protected $inputFilter;

    public function __construct($name = null)
    {
        parent::__construct('task');

        $this->add([
            'name' => 'id',
            'type' => 'hidden',
        ]);

        $this->add([
            'name' => 'title',
            'type' => 'text',
            'options' => [
                'label' => 'Título *',
            ],
            'attributes' => [
                'required' => true,
                'size' => 40,
                'maxlength' => 200,
                'class' => 'form-control',
                'placeholder' => 'Digite o título da tarefa',
                'id' => 'title'
            ],
        ]);

        $this->add([
            'name' => 'description',
            'type' => 'textarea',
            'options' => [
                'label' => 'Descrição',
            ],
            'attributes' => [
                'class' => 'form-control',
                'rows' => 4,
                'placeholder' => 'Descreva os detalhes da tarefa',
                'id' => 'description'
            ],
        ]);

        $this->add([
            'name' => 'status',
            'type' => 'select',
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
                'class' => 'form-control',
                'id' => 'status'
            ],
        ]);

        $this->add([
            'name' => 'priority',
            'type' => 'select',
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
                'class' => 'form-control',
                'id' => 'priority'
            ],
        ]);

        $this->add([
            'name' => 'due_date',
            'type' => 'datetime-local',
            'options' => [
                'label' => 'Data de Vencimento',
            ],
            'attributes' => [
                'class' => 'form-control',
                'id' => 'due_date'
            ],
        ]);

        $this->add([
            'name' => 'category_id',
            'type' => 'select',
            'options' => [
                'label' => 'Categoria',
                'value_options' => [], // Será preenchido no controller
            ],
            'attributes' => [
                'class' => 'form-control',
                'id' => 'category_id'
            ],
        ]);

        $this->add([
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => [
                'value' => 'Salvar',
                'id' => 'submitbutton',
                'class' => 'btn btn-primary',
            ],
        ]);
    }

    public function setInputFilter(InputFilterInterface $inputFilter)
    {
        $this->inputFilter = $inputFilter;
        return $this;
    }

    public function getInputFilter()
    {
        if (!$this->inputFilter) {
            $inputFilter = new InputFilter();

            // Validação do título
            $inputFilter->add([
                'name' => 'title',
                'required' => true,
                'filters' => [
                    ['name' => 'StringTrim'],
                    ['name' => 'StripTags'],
                ],
                'validators' => [
                    [
                        'name' => NotEmpty::class,
                        'options' => [
                            'messages' => [
                                NotEmpty::IS_EMPTY => 'O título da tarefa é obrigatório.',
                            ],
                        ],
                    ],
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'encoding' => 'UTF-8',
                            'min' => 3,
                            'max' => 200,
                            'messages' => [
                                StringLength::TOO_SHORT => 'O título deve ter pelo menos %min% caracteres.',
                                StringLength::TOO_LONG => 'O título não pode ter mais de %max% caracteres.',
                            ],
                        ],
                    ],
                ],
            ]);

            // Validação da descrição
            $inputFilter->add([
                'name' => 'description',
                'required' => false,
                'filters' => [
                    ['name' => 'StringTrim'],
                    ['name' => 'StripTags'],
                ],
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'encoding' => 'UTF-8',
                            'max' => 1000,
                            'messages' => [
                                StringLength::TOO_LONG => 'A descrição não pode ter mais de %max% caracteres.',
                            ],
                        ],
                    ],
                ],
            ]);

            // Validação do status
            $inputFilter->add([
                'name' => 'status',
                'required' => true,
                'validators' => [
                    [
                        'name' => InArray::class,
                        'options' => [
                            'haystack' => Task::getValidStatuses(),
                            'messages' => [
                                InArray::NOT_IN_ARRAY => 'Status selecionado é inválido.',
                            ],
                        ],
                    ],
                ],
            ]);

            // Validação da prioridade
            $inputFilter->add([
                'name' => 'priority',
                'required' => true,
                'validators' => [
                    [
                        'name' => InArray::class,
                        'options' => [
                            'haystack' => Task::getValidPriorities(),
                            'messages' => [
                                InArray::NOT_IN_ARRAY => 'Prioridade selecionada é inválida.',
                            ],
                        ],
                    ],
                ],
            ]);

            // Validação da data de vencimento
            $inputFilter->add([
                'name' => 'due_date',
                'required' => false,
                'validators' => [
                    [
                        'name' => Date::class,
                        'options' => [
                            'format' => 'Y-m-d\TH:i',
                            'messages' => [
                                Date::INVALID_DATE => 'Formato de data inválido.',
                                Date::FALSEFORMAT => 'A data deve estar no formato correto.',
                            ],
                        ],
                    ],
                ],
            ]);

            // Validação da categoria
            $inputFilter->add([
                'name' => 'category_id',
                'required' => false,
                'filters' => [
                    ['name' => 'ToInt'],
                ],
            ]);

            $this->inputFilter = $inputFilter;
        }

        return $this->inputFilter;
    }
}