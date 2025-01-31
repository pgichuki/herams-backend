<?php
declare(strict_types=1);

namespace prime\controllers\project;

use prime\components\Controller;
use prime\components\NotificationService;
use prime\helpers\ModelHydrator;
use prime\interfaces\AccessCheckInterface;
use prime\models\ar\Permission;
use prime\models\ar\Project;
use prime\models\forms\project\Create as ProjectCreate;
use prime\repositories\ProjectRepository;
use yii\base\Action;
use yii\web\Request;
use yii\web\User;

class Create extends Action
{
    public function run(
        AccessCheckInterface $accessCheck,
        NotificationService $notificationService,
        ProjectRepository $projectRepository,
        Request $request
    ) {
        $this->controller->layout = \prime\components\Controller::LAYOUT_ADMIN_TABS;

        $accessCheck->requireGlobalPermission(Permission::PERMISSION_CREATE_PROJECT);
        $model = new ProjectCreate();
        $hydrator = new ModelHydrator();
        if ($request->isPost) {
            $hydrator->hydrateFromRequestBody($model, $request);
            if ($model->validate()) {
                $projectId = $projectRepository->create($model);
                $notificationService->success(\Yii::t('app', "Project <strong>{project}</strong> created", [
                    'project' => $model->title
                ]));
                return $this->controller->redirect(['update', 'id' => $projectId]);
            }
        }

        return $this->controller->render('create', [
            'model' => $model
        ]);
    }
}
