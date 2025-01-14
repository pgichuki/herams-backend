<?php
declare(strict_types=1);

namespace prime\modules\Api\controllers\project;

use prime\models\ar\Permission;
use prime\models\ar\Project;
use yii\base\Action;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\User;

class Summary extends Action
{
    public function run(
        User $user,
        int $id
    ) {
        /** @var null|Project $project */
        $project = Project::find()->with('mainPages')->where(['id' => $id])->one();
        if (!isset($project)) {
            throw new NotFoundHttpException();
        }

        if (!$user->can(Permission::PERMISSION_SUMMARY, $project)) {
            throw new ForbiddenHttpException();
        }
        return $this->controller->asJson($project->toArray([], [
            'typeCounts',
            'functionalityCounts',
            'subjectAvailabilityCounts',
            'statusText'
        ]));
    }
}
