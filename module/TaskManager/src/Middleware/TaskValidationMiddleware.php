<?php

declare(strict_types=1);

namespace TaskManager\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Laminas\Diactoros\Response\JsonResponse;

/**
 * Middleware para validação de dados de entrada das tarefas
 */
class TaskValidationMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $method = $request->getMethod();
        $uri = $request->getUri()->getPath();

        // Apenas validar em rotas de criação e edição de tarefas
        if (($method === 'POST') && (strpos($uri, '/tasks/create') !== false || strpos($uri, '/tasks/edit') !== false)) {
            $data = $request->getParsedBody();

            if (is_array($data)) {
                $validationErrors = $this->validateTaskData($data, strpos($uri, '/create') !== false);

                if (!empty($validationErrors)) {
                    // Se for uma requisição AJAX, retornar JSON
                    if ($request->hasHeader('X-Requested-With')) {
                        return new JsonResponse([
                            'success' => false,
                            'message' => 'Dados inválidos',
                            'errors' => $validationErrors
                        ], 400);
                    }

                    // Para requisições normais, adicionar erros ao request
                    $request = $request->withAttribute('validation_errors', $validationErrors);
                }
            }
        }

        return $handler->handle($request);
    }

    /**
     * Valida os dados da tarefa
     */
    private function validateTaskData(array $data, bool $isCreate = true): array
    {
        $errors = [];

        // Validação do título
        if ($isCreate && empty($data['title'])) {
            $errors['title'][] = 'O título da tarefa é obrigatório';
        }

        if (isset($data['title'])) {
            $title = trim($data['title']);
            if (empty($title)) {
                $errors['title'][] = 'O título da tarefa não pode estar vazio';
            } elseif (strlen($title) < 3) {
                $errors['title'][] = 'O título deve ter pelo menos 3 caracteres';
            } elseif (strlen($title) > 200) {
                $errors['title'][] = 'O título não pode ter mais de 200 caracteres';
            } elseif (!preg_match('/^[a-zA-Z0-9\s\-_.,!?áéíóúàèìòùâêîôûãõçÁÉÍÓÚÀÈÌÒÙÂÊÎÔÛÃÕÇ]+$/u', $title)) {
                $errors['title'][] = 'O título contém caracteres inválidos';
            }
        }

        // Validação da descrição
        if (isset($data['description']) && strlen($data['description']) > 1000) {
            $errors['description'][] = 'A descrição não pode ter mais de 1000 caracteres';
        }

        // Validação do status
        if (isset($data['status'])) {
            $validStatuses = ['pending', 'in_progress', 'completed', 'cancelled'];
            if (!in_array($data['status'], $validStatuses)) {
                $errors['status'][] = 'Status inválido';
            }
        }

        // Validação da prioridade
        if (isset($data['priority'])) {
            $validPriorities = ['low', 'medium', 'high', 'urgent'];
            if (!in_array($data['priority'], $validPriorities)) {
                $errors['priority'][] = 'Prioridade inválida';
            }
        }

        // Validação da data de vencimento
        if (isset($data['due_date']) && !empty($data['due_date'])) {
            try {
                $dueDate = new \DateTime($data['due_date']);
                $now = new \DateTime();

                if ($isCreate && $dueDate < $now) {
                    $errors['due_date'][] = 'A data de vencimento não pode ser no passado';
                }
            } catch (\Exception $e) {
                $errors['due_date'][] = 'Data de vencimento inválida';
            }
        }

        // Sanitização de dados perigosos
        if (isset($data['title'])) {
            $data['title'] = $this->sanitizeInput($data['title']);
        }

        if (isset($data['description'])) {
            $data['description'] = $this->sanitizeInput($data['description']);
        }

        return $errors;
    }

    /**
     * Sanitiza entrada de dados
     */
    private function sanitizeInput(string $input): string
    {
        // Remove tags HTML perigosas
        $input = strip_tags($input);

        // Remove caracteres de controle
        $input = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $input);

        // Trim espaços
        $input = trim($input);

        return $input;
    }
}
