<?php

declare(strict_types=1);

namespace Auth\Factory;

use Auth\Service\AuthenticationManager;
use Auth\Service\AuthService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class AuthenticationManagerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): AuthenticationManager
    {
        $authService = $container->get(AuthService::class);
        return new AuthenticationManager($authService);
    }
}
