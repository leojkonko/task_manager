<?php

namespace TaskManager\Form;

use Laminas\Form\Form;
use Laminas\InputFilter\InputFilter;
use Laminas\InputFilter\InputFilterAwareInterface;
use Laminas\InputFilter\InputFilterInterface;
use Laminas\Validator;
use Laminas\Filter;
use TaskManager\Model\Task;

class TaskForm extends Form implements InputFilterAwareInterface
{
    private $inputFilter;

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
                'data-validation' => 'required'
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
                'maxlength' => 2000,
                'placeholder' => 'Descreva os detalhes da tarefa'
            ],
        ]);

        // Usar constantes da classe Task para os options
        $statusOptions = [];
        foreach (Task::getValidStatuses() as $status) {
            $task = new Task();
            $task->status = $status;
            $statusOptions[$status] = $task->getStatusLabel();
        }

        $this->add([
            'name' => 'status',
            'type' => 'select',
            'options' => [
                'label' => 'Status',
                'value_options' => $statusOptions,
            ],
            'attributes' => [
                'class' => 'form-control',
            ],
        ]);

        // Usar constantes da classe Task para prioridades
        $priorityOptions = [];
        foreach (Task::getValidPriorities() as $priority) {
            $task = new Task();
            $task->priority = $priority;
            $priorityOptions[$priority] = $task->getPriorityLabel();
        }

        $this->add([
            'name' => 'priority',
            'type' => 'select',
            'options' => [
                'label' => 'Prioridade',
                'value_options' => $priorityOptions,
            ],
            'attributes' => [
                'class' => 'form-control',
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
                'min' => date('Y-m-d\TH:i'), // Data mínima é hoje
            ],
        ]);

        $this->add([
            'name' => 'category_id',
            'type' => 'select',
            'options' => [
                'label' => 'Categoria',
                'value_options' => [], // Será preenchido no controller
                'empty_option' => 'Selecione uma categoria (opcional)',
            ],
            'attributes' => [
                'class' => 'form-control',
            ],
        ]);

        $this->add([
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => [
                'value' => 'Salvar',
                'id'    => 'submitbutton',
                'class' => 'btn btn-primary',
            ],
        ]);

        // Configurar input filter
        $this->setInputFilter($this->createInputFilter());
    }

    /**
     * Criar input filter com validações robustas
     */
    private function createInputFilter(): InputFilter
    {
        $inputFilter = new InputFilter();

        // Validação para o título
        $inputFilter->add([
            'name' => 'title',
            'required' => true,
            'filters' => [
                ['name' => Filter\StripTags::class],
                ['name' => Filter\StringTrim::class],
            ],
            'validators' => [
                [
                    'name' => Validator\NotEmpty::class,
                    'options' => [
                        'messages' => [
                            Validator\NotEmpty::IS_EMPTY => 'O título da tarefa é obrigatório.',
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
                            Validator\StringLength::TOO_SHORT => 'O título deve ter pelo menos 3 caracteres.',
                            Validator\StringLength::TOO_LONG => 'O título não pode ter mais de 200 caracteres.',
                        ],
                    ],
                ],
            ],
        ]);

        // Validação para a descrição
        $inputFilter->add([
            'name' => 'description',
            'required' => false,
            'filters' => [
                ['name' => Filter\StripTags::class],
                ['name' => Filter\StringTrim::class],
            ],
            'validators' => [
                [
                    'name' => Validator\StringLength::class,
                    'options' => [
                        'encoding' => 'UTF-8',
                        'max' => 2000,
                        'messages' => [
                            Validator\StringLength::TOO_LONG => 'A descrição não pode ter mais de 2000 caracteres.',
                        ],
                    ],
                ],
            ],
        ]);

        // Validação para o status
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
                        'haystack' => Task::getValidStatuses(),
                        'messages' => [
                            Validator\InArray::NOT_IN_ARRAY => 'Status inválido.',
                        ],
                    ],
                ],
            ],
        ]);

        // Validação para a prioridade
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
                        'haystack' => Task::getValidPriorities(),
                        'messages' => [
                            Validator\InArray::NOT_IN_ARRAY => 'Prioridade inválida.',
                        ],
                    ],
                ],
            ],
        ]);

        // Validação para a data de vencimento
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
                        'format' => ['Y-m-d\TH:i', 'Y-m-d H:i:s', 'Y-m-d H:i'],
                        'messages' => [
                            Validator\Date::INVALID_DATE => 'Formato de data inválido.',
                        ],
                    ],
                ],
            ],
        ]);

        // Validação para category_id
        $inputFilter->add([
            'name' => 'category_id',
            'required' => false,
            'filters' => [
                ['name' => Filter\ToInt::class],
            ],
            'validators' => [
                [
                    'name' => Validator\Digits::class,
                    'options' => [
                        'messages' => [
                            Validator\Digits::NOT_DIGITS => 'ID da categoria deve ser numérico.',
                        ],
                    ],
                ],
            ],
        ]);

        return $inputFilter;
    }

    /**
     * Set input filter
     */
    public function setInputFilter(InputFilterInterface $inputFilter)
    {
        $this->inputFilter = $inputFilter;
        return $this;
    }

    /**
     * Get input filter
     */
    public function getInputFilter()
    {
        return $this->inputFilter;
    }

    /**
     * Definir opções de categoria dinamicamente
     */
    public function setCategoryOptions(array $categories)
    {
        $options = ['' => 'Selecione uma categoria (opcional)'];
        foreach ($categories as $id => $name) {
            $options[$id] = $name;
        }
        
        $this->get('category_id')->setValueOptions($options);
        return $this;
    }

    /**
     * Validar usando a classe Task também
     */
    public function isValid()
    {
        $isFormValid = parent::isValid();
        
        if (!$isFormValid) {
            return false;
        }

        // Validação adicional usando a classe Task
        $task = new Task();
        $task->exchangeArray($this->getData());
        
        $taskErrors = $task->validate();
        if (!empty($taskErrors)) {
            foreach ($taskErrors as $field => $message) {
                $this->get($field)->setMessages([$message]);
            }
            return false;
        }

        return true;
    }
}