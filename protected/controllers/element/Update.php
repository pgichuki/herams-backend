<?php


namespace prime\controllers\element;


use prime\models\ar\Element;
use prime\models\permissions\Permission;
use yii\base\Action;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Request;
use yii\web\Session;
use yii\web\User;

class Update extends Action
{

    public function run(
        Request $request,
        Session $session,
        User $user,
        int $id
    ) {
        $model = Element::findOne(['id' => $id]);

        if (!isset($model)) {
            throw new NotFoundHttpException();
        }

        if (!$user->can(Permission::PERMISSION_ADMIN, $model->page->project)) {
            throw new ForbiddenHttpException();
        }


        if ($request->isPut) {
            if ($model->load($request->bodyParams) && $model->save()) {
                $session->setFlash(
                    'elementUpdated',
                    [
                        'type' => \kartik\widgets\Growl::TYPE_SUCCESS,
                        'text' => "Element updated",
                        'icon' => 'glyphicon glyphicon-ok'
                    ]
                );

                return $this->controller->refresh();
            }
        }

        return $this->controller->render('update', [
            'model' => $model,
            'project' => $model->page->project,
            'page' => $model->page
        ]);
    }

}