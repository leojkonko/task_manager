<?php

declare(strict_types=1);

namespace TaskManager\Exception;

use Laminas\Mvc\MvcEvent;
use Laminas\View\Model\JsonModel;
use Laminas\Http\Response;

/**
 * Handler para tratar exceções de validação
 */
class ValidationExceptionHandler
{
    public function __invoke(MvcEvent $event)
    {
        $exception = $event->getParam('exception');

        if (!$exception instanceof \InvalidArgumentException) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();

        // Se for uma requisição AJAX, retornar JSON
        if ($request->getHeader('X-Requested-With')) {
            $jsonModel = new JsonModel([
                'success' => false,
                'message' => $exception->getMessage(),
                'error_code' => 'VALIDATION_ERROR'
            ]);

            $response->setStatusCode(400);
            $event->setResult($jsonModel);
            $event->setError(false);
            $event->stopPropagation();

            return $response;
        }

        // Para requisições normais, definir mensagem de erro
        $event->getApplication()
            ->getServiceManager()
            ->get('ControllerPluginManager')
            ->get('FlashMessenger')
            ->addErrorMessage($exception->getMessage());

        // Redirecionar de volta
        $router = $event->getRouter();
        $url = $router->assemble([], ['name' => 'task-manager']);

        $response->getHeaders()->addHeaderLine('Location', $url);
        $response->setStatusCode(302);
        $event->setResult($response);
        $event->setError(false);
        $event->stopPropagation();

        return $response;
    }
}
