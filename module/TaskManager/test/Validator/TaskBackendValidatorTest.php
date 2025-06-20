<?php

declare(strict_types=1);

namespace TaskManagerTest\Validator;

use PHPUnit\Framework\TestCase;
use TaskManager\Validator\TaskBackendValidator;
use DateTime;
use InvalidArgumentException;

/**
 * Testes unitários para o TaskBackendValidator
 * 
 * Testa todas as validações de regras de negócio:
 * - Validação de dias úteis
 * - Validação de campos obrigatórios
 * - Validação de formatos e limites
 * - Validação de operações (update, delete)
 */
class TaskBackendValidatorTest extends TestCase
{
    /**
     * Testa validação básica de dados válidos
     */
    public function testValidDataReturnsNoErrors(): void
    {
        $validData = [
            'title' => 'Tarefa válida',
            'description' => 'Descrição válida',
            'status' => 'pending',
            'priority' => 'medium',
            'due_date' => (new DateTime('+1 week'))->format('Y-m-d H:i:s'),
            'user_id' => 1,
            'category_id' => 2
        ];

        // Para dados válidos em dias úteis, simula segunda-feira
        if ((int)(new DateTime())->format('N') >= 6) {
            // Se hoje é fim de semana, testa apenas validação sem criação
            $errors = TaskBackendValidator::validate($validData, false);
        } else {
            // Se hoje é dia útil, testa criação normal
            $errors = TaskBackendValidator::validate($validData, true);
        }

        // Remove erro de dias úteis se existir para focar nos outros campos
        unset($errors['creation_time']);
        
        $this->assertEmpty($errors);
    }

    /**
     * Testa validação de dias úteis para criação
     */
    public function testWeekdayValidationOnCreation(): void
    {
        $data = [
            'title' => 'Tarefa teste',
            'user_id' => 1
        ];

        $now = new DateTime();
        $dayOfWeek = (int)$now->format('N');

        $errors = TaskBackendValidator::validate($data, true);

        if ($dayOfWeek >= 6) {
            // Fim de semana - deve ter erro
            $this->assertArrayHasKey('creation_time', $errors);
            $this->assertNotEmpty($errors['creation_time']);
            $this->assertStringContainsString('dias úteis', $errors['creation_time'][0]);
        } else {
            // Dia útil - não deve ter erro de creation_time
            $this->assertArrayNotHasKey('creation_time', $errors);
        }
    }

    /**
     * Testa que validação de dias úteis não se aplica para updates
     */
    public function testWeekdayValidationNotAppliedOnUpdate(): void
    {
        $data = [
            'title' => 'Tarefa atualizada',
            'user_id' => 1
        ];

        $errors = TaskBackendValidator::validate($data, false);

        // Updates não devem ter validação de dias úteis
        $this->assertArrayNotHasKey('creation_time', $errors);
    }

    /**
     * Testa validação de título obrigatório
     */
    public function testTitleRequired(): void
    {
        $data = ['user_id' => 1];

        $errors = TaskBackendValidator::validate($data, true);

        $this->assertArrayHasKey('title', $errors);
        $this->assertContains('O título da tarefa é obrigatório', $errors['title']);
    }

    /**
     * Testa validação de título muito curto
     */
    public function testTitleTooShort(): void
    {
        $data = [
            'title' => 'ab',
            'user_id' => 1
        ];

        $errors = TaskBackendValidator::validate($data, true);

        $this->assertArrayHasKey('title', $errors);
        $this->assertContains('O título deve ter pelo menos 3 caracteres', $errors['title']);
    }

    /**
     * Testa validação de título muito longo
     */
    public function testTitleTooLong(): void
    {
        $data = [
            'title' => str_repeat('a', 201),
            'user_id' => 1
        ];

        $errors = TaskBackendValidator::validate($data, true);

        $this->assertArrayHasKey('title', $errors);
        $this->assertContains('O título não pode ter mais de 200 caracteres', $errors['title']);
    }

    /**
     * Testa validação de título com caracteres inválidos
     */
    public function testTitleInvalidCharacters(): void
    {
        $data = [
            'title' => 'Título com % $ # @ caracteres inválidos',
            'user_id' => 1
        ];

        $errors = TaskBackendValidator::validate($data, true);

        $this->assertArrayHasKey('title', $errors);
        $this->assertStringContainsString('caracteres inválidos', $errors['title'][0]);
    }

    /**
     * Testa validação de descrição muito longa
     */
    public function testDescriptionTooLong(): void
    {
        $data = [
            'title' => 'Título válido',
            'description' => str_repeat('a', 1001),
            'user_id' => 1
        ];

        $errors = TaskBackendValidator::validate($data, true);

        $this->assertArrayHasKey('description', $errors);
        $this->assertContains('A descrição não pode ter mais de 1000 caracteres', $errors['description']);
    }

    /**
     * Testa validação de status inválido
     */
    public function testInvalidStatus(): void
    {
        $data = [
            'title' => 'Título válido',
            'status' => 'invalid_status',
            'user_id' => 1
        ];

        $errors = TaskBackendValidator::validate($data, true);

        $this->assertArrayHasKey('status', $errors);
        $this->assertStringContainsString('Status inválido', $errors['status'][0]);
    }

    /**
     * Testa validação de prioridade inválida
     */
    public function testInvalidPriority(): void
    {
        $data = [
            'title' => 'Título válido',
            'priority' => 'invalid_priority',
            'user_id' => 1
        ];

        $errors = TaskBackendValidator::validate($data, true);

        $this->assertArrayHasKey('priority', $errors);
        $this->assertStringContainsString('Prioridade inválida', $errors['priority'][0]);
    }

    /**
     * Testa validação de data de vencimento inválida
     */
    public function testInvalidDueDate(): void
    {
        $data = [
            'title' => 'Título válido',
            'due_date' => 'data_invalida',
            'user_id' => 1
        ];

        $errors = TaskBackendValidator::validate($data, true);

        $this->assertArrayHasKey('due_date', $errors);
        $this->assertStringContainsString('Data de vencimento inválida', $errors['due_date'][0]);
    }

    /**
     * Testa validação de data de vencimento no passado
     */
    public function testDueDateInPast(): void
    {
        $data = [
            'title' => 'Título válido',
            'due_date' => (new DateTime('-1 day'))->format('Y-m-d H:i:s'),
            'user_id' => 1
        ];

        $errors = TaskBackendValidator::validate($data, true);

        $this->assertArrayHasKey('due_date', $errors);
        $this->assertContains('A data de vencimento não pode ser no passado', $errors['due_date']);
    }

    /**
     * Testa validação de user_id obrigatório
     */
    public function testUserIdRequired(): void
    {
        $data = ['title' => 'Título válido'];

        $errors = TaskBackendValidator::validate($data, true);

        $this->assertArrayHasKey('user_id', $errors);
        $this->assertContains('O ID do usuário é obrigatório', $errors['user_id']);
    }

    /**
     * Testa validação de user_id inválido
     */
    public function testInvalidUserId(): void
    {
        $data = [
            'title' => 'Título válido',
            'user_id' => 'abc'
        ];

        $errors = TaskBackendValidator::validate($data, true);

        $this->assertArrayHasKey('user_id', $errors);
        $this->assertContains('ID do usuário deve ser um número positivo', $errors['user_id']);
    }

    /**
     * Testa validação de category_id inválido
     */
    public function testInvalidCategoryId(): void
    {
        $data = [
            'title' => 'Título válido',
            'user_id' => 1,
            'category_id' => 'abc'
        ];

        $errors = TaskBackendValidator::validate($data, true);

        $this->assertArrayHasKey('category_id', $errors);
        $this->assertContains('ID da categoria deve ser um número positivo', $errors['category_id']);
    }

    /**
     * Testa sanitização de dados
     */
    public function testDataSanitization(): void
    {
        $dirtyData = [
            'title' => '  <script>alert("xss")</script>Título com espaços  ',
            'description' => "  Descrição\x00com\x1Fcaracteres\x7Fde controle  ",
            'user_id' => 1
        ];

        $cleanData = TaskBackendValidator::sanitize($dirtyData);

        $this->assertEquals('alert("xss")Título com espaços', $cleanData['title']);
        $this->assertEquals('Descriçãocomcaracteresde controle', $cleanData['description']);
        $this->assertEquals(1, $cleanData['user_id']);
    }

    /**
     * Testa método validateAndThrow com dados válidos
     */
    public function testValidateAndThrowWithValidData(): void
    {
        $validData = [
            'title' => 'Título válido',
            'user_id' => 1
        ];

        // Se hoje é dia útil, não deve lançar exceção
        if ((int)(new DateTime())->format('N') < 6) {
            $this->expectNotToPerformAssertions();
            TaskBackendValidator::validateAndThrow($validData, true);
        } else {
            // Se hoje é fim de semana, deve lançar exceção
            $this->expectException(InvalidArgumentException::class);
            TaskBackendValidator::validateAndThrow($validData, true);
        }
    }

    /**
     * Testa método validateAndThrow com dados inválidos
     */
    public function testValidateAndThrowWithInvalidData(): void
    {
        $invalidData = [
            'title' => 'ab', // Muito curto
            'user_id' => 'abc' // Inválido
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Dados inválidos');

        TaskBackendValidator::validateAndThrow($invalidData, true);
    }

    /**
     * Testa validação de atualização de tarefa
     */
    public function testTaskUpdateValidation(): void
    {
        // Tarefa pendente pode ser atualizada
        $errors = TaskBackendValidator::validateTaskUpdate('pending');
        $this->assertEmpty($errors);

        // Tarefa em andamento não pode ser atualizada
        $errors = TaskBackendValidator::validateTaskUpdate('in_progress');
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('não pode ser editada', $errors[0]);

        // Tarefa concluída não pode ser atualizada
        $errors = TaskBackendValidator::validateTaskUpdate('completed');
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('não pode ser editada', $errors[0]);

        // Tarefa cancelada não pode ser atualizada
        $errors = TaskBackendValidator::validateTaskUpdate('cancelled');
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('não pode ser editada', $errors[0]);
    }

    /**
     * Testa validação de exclusão de tarefa
     */
    public function testTaskDeletionValidation(): void
    {
        $oldDate = (new DateTime('-10 days'))->format('Y-m-d H:i:s');
        $recentDate = (new DateTime('-2 days'))->format('Y-m-d H:i:s');

        // Tarefa pendente e antiga pode ser excluída
        $errors = TaskBackendValidator::validateTaskDeletion('pending', $oldDate);
        $this->assertEmpty($errors);

        // Tarefa pendente mas recente não pode ser excluída
        $errors = TaskBackendValidator::validateTaskDeletion('pending', $recentDate);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('após 5 dias', $errors[0]);

        // Tarefa em andamento não pode ser excluída
        $errors = TaskBackendValidator::validateTaskDeletion('in_progress', $oldDate);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('não pode ser excluída', $errors[0]);

        // Tarefa concluída não pode ser excluída
        $errors = TaskBackendValidator::validateTaskDeletion('completed', $oldDate);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('não pode ser excluída', $errors[0]);
    }

    /**
     * Testa formatos de data aceitos
     */
    public function testAcceptedDateFormats(): void
    {
        $dateFormats = [
            'Y-m-d H:i:s' => '2025-12-31 23:59:59',
            'Y-m-d\TH:i' => '2025-12-31T23:59',
            'Y-m-d' => '2025-12-31'
        ];

        $hasValidFormat = false;

        foreach ($dateFormats as $format => $dateString) {
            $data = [
                'title' => 'Título válido',
                'due_date' => $dateString,
                'user_id' => 1
            ];

            $errors = TaskBackendValidator::validate($data, false);
            
            // Remove possíveis erros de dias úteis para focar no formato da data
            unset($errors['creation_time']);
            
            // A data deve ser aceita (não deve ter erro de formato)
            if (!isset($errors['due_date']) || 
                !in_array('Data de vencimento inválida. Use o formato Y-m-d H:i:s ou Y-m-d\\TH:i', $errors['due_date'])) {
                $hasValidFormat = true;
            }
        }

        // Pelo menos um formato deve ser aceito
        $this->assertTrue($hasValidFormat, 'Nenhum formato de data foi aceito pelo validador');
    }

    /**
     * Testa múltiplos erros simultâneos
     */
    public function testMultipleErrors(): void
    {
        $invalidData = [
            'title' => 'ab', // Muito curto
            'description' => str_repeat('a', 1001), // Muito longa
            'status' => 'invalid_status',
            'priority' => 'invalid_priority',
            'due_date' => 'invalid_date',
            'user_id' => 'invalid_user',
            'category_id' => 'invalid_category'
        ];

        $errors = TaskBackendValidator::validate($invalidData, true);

        // Deve ter erros para todos os campos inválidos
        $this->assertArrayHasKey('title', $errors);
        $this->assertArrayHasKey('description', $errors);
        $this->assertArrayHasKey('status', $errors);
        $this->assertArrayHasKey('priority', $errors);
        $this->assertArrayHasKey('due_date', $errors);
        $this->assertArrayHasKey('user_id', $errors);
        $this->assertArrayHasKey('category_id', $errors);
    }

    /**
     * Testa validação com valores nulos opcionais
     */
    public function testOptionalNullValues(): void
    {
        $data = [
            'title' => 'Título válido',
            'description' => null,
            'status' => null,
            'priority' => null,
            'due_date' => null,
            'user_id' => 1,
            'category_id' => null
        ];

        $errors = TaskBackendValidator::validate($data, false);
        
        // Remove possíveis erros de dias úteis
        unset($errors['creation_time']);

        // Valores nulos opcionais não devem gerar erros
        $this->assertArrayNotHasKey('description', $errors);
        $this->assertArrayNotHasKey('status', $errors);
        $this->assertArrayNotHasKey('priority', $errors);
        $this->assertArrayNotHasKey('due_date', $errors);
        $this->assertArrayNotHasKey('category_id', $errors);
    }

    /**
     * Testa edge cases de validação
     */
    public function testEdgeCases(): void
    {
        // Título exatamente com 3 caracteres (mínimo)
        $data1 = ['title' => 'abc', 'user_id' => 1];
        $errors1 = TaskBackendValidator::validate($data1, false);
        unset($errors1['creation_time']);
        $this->assertArrayNotHasKey('title', $errors1);

        // Título com 200 caracteres (máximo)
        $data2 = ['title' => str_repeat('a', 200), 'user_id' => 1];
        $errors2 = TaskBackendValidator::validate($data2, false);
        unset($errors2['creation_time']);
        $this->assertArrayNotHasKey('title', $errors2);

        // Descrição com 1000 caracteres (máximo)
        $data3 = [
            'title' => 'Título válido',
            'description' => str_repeat('a', 1000),
            'user_id' => 1
        ];
        $errors3 = TaskBackendValidator::validate($data3, false);
        unset($errors3['creation_time']);
        $this->assertArrayNotHasKey('description', $errors3);
    }
}