<?php

/**
 * List of enabled modules for this application.
 *
 * This should be an array of module namespaces used in the application.
 */
return [
    'Laminas\Mvc\Plugin\FlashMessenger',
    'Laminas\Session',
    'Laminas\Db',
    'Laminas\Router',
    'Laminas\Validator',
    'Laminas\Form', // Added this line to enable form view helpers
    'Auth', // Módulo de autenticação
    'Application',
    'TaskManager',
];
