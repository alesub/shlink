<?php

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;
use Zend\ServiceManager\ServiceManager;

chdir(dirname(__DIR__));

require 'vendor/autoload.php';

// If the Dotenv class exists, load env vars and enable errors
if (class_exists(Dotenv::class)) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    $dotenv = new Dotenv(true);
    $dotenv->load(__DIR__ . '/../.env');
}

// Build container
$config = require __DIR__ . '/config.php';
$container = new ServiceManager($config['dependencies']);
$container->setService('config', $config);
return $container;
