<?php
declare(strict_types=1);

namespace prime\controllers;

use prime\actions\CreateChildAction;
use prime\actions\DeleteAction;
use prime\actions\ExportAction;
use prime\components\Controller;
use prime\controllers\workspace\Facilities;
use prime\controllers\workspace\Import;
use prime\controllers\workspace\Limesurvey;
use prime\controllers\workspace\Refresh;
use prime\controllers\workspace\Responses;
use prime\controllers\workspace\Share;
use prime\controllers\workspace\Update;
use prime\helpers\ModelHydrator;
use prime\models\ar\Permission;
use prime\models\ar\Workspace;
use prime\objects\Breadcrumb;
use prime\queries\ResponseQuery;
use prime\repositories\ProjectRepository;
use prime\repositories\WorkspaceRepository;
use prime\values\IntegerId;
use prime\values\ProjectId;
use prime\values\WorkspaceId;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\Request;
use yii\web\User;

class WorkspaceController extends Controller
{
    public $layout = self::LAYOUT_ADMIN_TABS;
    public $defaultAction = 'facilities';

    public function __construct($id, $module,
        private ProjectRepository $projectRepository,
        private WorkspaceRepository $workspaceRepository,
        $config = [])
    {
        parent::__construct($id, $module, $config);
    }

    public function beforeAction($action)
    {
        $breadcrumbCollection = $this->view->getBreadcrumbCollection()
            ->add((new Breadcrumb())->setUrl(['/project/index'])->setLabel(\Yii::t('app', 'Projects')))
        ;

        if (in_array($action->id, ['create', 'import']) && $projectId = (int) $this->request->getQueryParam('project_id')) {
            $project = $this->projectRepository->retrieveForBreadcrumb(new ProjectId($projectId));
            $breadcrumbCollection->add((new Breadcrumb())->setUrl(['/project/workspaces', 'id' => $project->getId()])->setLabel($project->getTitle()));
        } elseif ($id = $this->request->getQueryParam('id')) {
            $model = $this->workspaceRepository->retrieveForBreadcrumb(new WorkspaceId((int) $id));
            $project = $this->projectRepository->retrieveForBreadcrumb($model->getProjectId());
            $breadcrumbCollection->add((new Breadcrumb())->setUrl(['/project/workspaces', 'id' => $project->getId()])->setLabel($project->getTitle()));
        }

        return parent::beforeAction($action);
    }

    /**
     * Inject the model for the tab menu into the view.
     */
    public function render($view, $params = [])
    {
        if (!isset($params['tabMenuModel']) && $this->request->getQueryParam('id')) {
            $workspaceId = new WorkspaceId((int) $this->request->getQueryParam('id'));
            $params['tabMenuModel'] = $this->workspaceRepository->retrieveForTabMenu($workspaceId);
        }
        return parent::render($view, $params);
    }


    public function actions(): array
    {
        return [
            'responses' => Responses::class,
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
            'facilities' => Facilities::class,
            'update' => Update::class,
            'create' => static function (
                string $id,
                Controller $controller,
                ProjectRepository $projectRepository,
                WorkspaceRepository $repository,
                ModelHydrator $modelHydrator
) {
                $action = new CreateChildAction($id, $controller, $repository, $projectRepository, $modelHydrator);
                $action->paramName = 'project_id';
                return $action;
            },
            'share' => Share::class,
            'import' => Import::class,
            'refresh' => Refresh::class,
            'delete' => [
                'class' => DeleteAction::class,
                'query' => Workspace::find(),
                'redirect' => function (Workspace $workspace) {
                    return ['/project/workspaces', 'id' => $workspace->tool_id];
                }
            ],

        ];
    }



    public function behaviors(): array
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
