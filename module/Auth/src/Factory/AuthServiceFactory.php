<?php

declare(strict_types=1);

namespace Auth\Factory;

use Auth\Service\AuthService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use PDO;

class AuthServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): AuthService
    {
        $pdo = $container->get(PDO::class);
        return new AuthService($pdo);
    }
}
