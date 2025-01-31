<?php


namespace prime\controllers;

use prime\actions\DeleteAction;
use prime\actions\ExportAction;
use prime\components\Controller;
use prime\controllers\workspace\Configure;
use prime\controllers\workspace\Create;
use prime\controllers\workspace\Import;
use prime\controllers\workspace\Limesurvey;
use prime\controllers\workspace\Refresh;
use prime\controllers\workspace\RequestAccess;
use prime\controllers\workspace\Responses;
use prime\controllers\workspace\Share;
use prime\controllers\workspace\Update;
use prime\controllers\workspace\View;
use prime\models\ar\Permission;
use prime\models\ar\Workspace;
use prime\queries\ResponseQuery;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\Request;
use yii\web\User;

class WorkspaceController extends Controller
{
    public $layout = self::LAYOUT_ADMIN;


    public function actions(): array
    {
        return [
            'responses' => Responses::class,
            'configure' => Configure::class,
            'export' => [
                'class' => ExportAction::class,
                'subject' => static function (Request $request) {
                      return Workspace::findOne(['id' => $request->getQueryParam('id')]);
                },
                'responseQuery' => static function (Workspace $workspace): ResponseQuery {
                    return $workspace->getResponses();
                },
                'surveyFinder' => function (Workspace $workspace) {
                    return $workspace->project->getSurvey();
                },
                'checkAccess' => function (Workspace $workspace, User $user) {
                    return $user->can(Permission::PERMISSION_EXPORT, $workspace);
                }
            ],
            'limesurvey' => Limesurvey::class,
            'update' => Update::class,
            'create' => Create::class,
            'share' => Share::class,
            'import' => Import::class,
            'refresh' => Refresh::class,
            'view' => View::class,
            'delete' => [
                'class' => DeleteAction::class,
                'query' => Workspace::find(),
                'redirect' => function (Workspace $workspace) {
                    return ['/project/workspaces', 'id' => $workspace->tool_id];
                }
            ],
            'request-access' => RequestAccess::class,
        ];
    }

    public function behaviors()
    {
        return ArrayHelper::merge(
            parent::behaviors(),
            [
                'verb' => [
                    'class' => VerbFilter::class,
                    'actions' => [
                        'create' => ['get', 'post']
                    ]
                ],
                'access' => [
                    'rules' => [
                        [
                            'allow' => true,
                            'roles' => ['@'],
                        ],
                    ]
                ],
            ]
        );
    }
}
