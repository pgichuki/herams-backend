<?php

declare(strict_types=1);

// Override some things in the container.
use SamIT\abac\repositories\PreloadingSourceRepository;
use SamIT\Yii2\abac\ActiveRecordRepository;
use yii\caching\ArrayCache;
use yii\caching\CacheInterface;
use yii\di\Container;

\Yii::$container->set(
    PreloadingSourceRepository::class,
    fn (Container $container) => new PreloadingSourceRepository($container->get(ActiveRecordRepository::class)),
);

\Yii::$container->set(
    CacheInterface::class,
    ArrayCache::class
);
$env = new \prime\components\InsecureSecretEnvironment();
$config = require 'web.php';
$config['components']['limesurvey'] = \prime\tests\_helpers\LimesurveyStub::class;



return $config;
