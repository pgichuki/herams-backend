<?php

declare(strict_types=1);

namespace prime\modules\Api\controllers;

use prime\modules\Api\controllers\project\Index;
use prime\modules\Api\controllers\project\Summary;
use prime\modules\Api\controllers\project\Variables;
use yii\filters\AccessControl;
use yii\web\Controller;

class ProjectController extends Controller
{
    public function behaviors(): array
    {
        return [
            AccessControl::class => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['index', 'summary']
                    ],
                    [
                        'allow' => true,
                        'roles' => ['@'],
                        'actions' => ['variables']
                    ],
                    [
                        'allow' => false
                    ]
                ]
            ]
        ];
    }

    public function actions(): array
    {
        return [
            'index' => Index::class,
            'summary' => Summary::class,
            'variables' => Variables::class
        ];
    }
}
