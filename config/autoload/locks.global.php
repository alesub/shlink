<?php

declare(strict_types=1);

use Shlinkio\Shlink\Common\Cache\RedisFactory;
use Shlinkio\Shlink\Common\Lock\RetryLockStoreDelegatorFactory;
use Shlinkio\Shlink\Common\Logger\LoggerAwareDelegatorFactory;
use Symfony\Component\Lock;
use Zend\ServiceManager\AbstractFactory\ConfigAbstractFactory;

// This class alias tricks the ConfigAbstractFactory to return Lock\Factory instances even with a different service name
$localLockFactory = 'Shlinkio\Shlink\LocalLockFactory';
class_alias(Lock\LockFactory::class, $localLockFactory);

return [

    'locks' => [
        'locks_dir' => __DIR__ . '/../../data/locks',
    ],

    'dependencies' => [
        'factories' => [
            Lock\Store\FlockStore::class => ConfigAbstractFactory::class,
            Lock\Store\RedisStore::class => ConfigAbstractFactory::class,
            Lock\LockFactory::class => ConfigAbstractFactory::class,
            $localLockFactory => ConfigAbstractFactory::class,
        ],
        'aliases' => [
            // With this config, a user could alias 'lock_store' => 'redis_lock_store' to override the default
            'lock_store' => 'local_lock_store',

            'redis_lock_store' => Lock\Store\RedisStore::class,
            'local_lock_store' => Lock\Store\FlockStore::class,
        ],
        'delegators' => [
            Lock\Store\RedisStore::class => [
                RetryLockStoreDelegatorFactory::class,
            ],
            Lock\LockFactory::class => [
                LoggerAwareDelegatorFactory::class,
            ],
        ],
    ],

    ConfigAbstractFactory::class => [
        Lock\Store\FlockStore::class => ['config.locks.locks_dir'],
        Lock\Store\RedisStore::class => [RedisFactory::SERVICE_NAME],
        Lock\LockFactory::class => ['lock_store'],
        $localLockFactory => ['local_lock_store'],
    ],

];
