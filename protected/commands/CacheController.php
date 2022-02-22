<?php

declare(strict_types=1);

namespace prime\commands;

use prime\components\LimesurveyDataProvider;
use prime\helpers\LimesurveyDataLoader;
use prime\models\ar\Project;
use prime\models\ar\ResponseForLimesurvey;
use prime\models\ar\WorkspaceForLimesurvey;
use yii\helpers\Console;

class CacheController extends \yii\console\controllers\CacheController
{
    public function actionResync(LimesurveyDataProvider $limesurveyDataProvider)
    {
        /** @var Project $project */
        foreach (Project::find()->each() as $project) {
            $this->stdout("Removing all responses for project {$project->title}\n", Console::FG_CYAN);
            ResponseForLimesurvey::deleteAll([
                'and',
                ['workspace_id' => $project->getWorkspaces()->select('id')],
                ['not', ['id' => null]]
            ]);
            $this->stdout("Starting cache warmup for project {$project->title}\n", Console::FG_CYAN);
            try {
                $this->warmupProject($limesurveyDataProvider, $project);
            } catch (\Throwable $t) {
                $this->stderr($t->getMessage(), Console::FG_RED);
            }
        }
    }

    public function actionWarmup(LimesurveyDataProvider $limesurveyDataProvider)
    {
        /** @var Project $project */
        foreach (Project::find()->each() as $project) {
            $this->stdout("Starting cache warmup for project {$project->title}\n", Console::FG_CYAN);
            try {
                $this->warmupProject($limesurveyDataProvider, $project);
            } catch (\Throwable $t) {
                $this->stderr($t->getMessage(), Console::FG_RED);
            }
        }
    }

    public function actionWarmupSurveys(LimesurveyDataProvider $limesurveyDataProvider): void
    {
        foreach ($limesurveyDataProvider->listSurveys() as $survey) {
            $this->actionWarmupSurvey($limesurveyDataProvider, (int) $survey['sid']);
        }
    }

    public function actionWarmupSurvey(LimesurveyDataProvider $limesurveyDataProvider, int $id)
    {
        $this->stdout("Refreshing survey structure ($id)...", Console::FG_CYAN);
        foreach ($limesurveyDataProvider->getSurvey($id)->getGroups() as $group) {
            $this->stdout('.', Console::FG_PURPLE);
            $group->getQuestions();
        }
        $this->stdout("OK\n", Console::FG_GREEN);
    }

    public function actionWarmupProject(
        LimesurveyDataProvider $limesurveyDataProvider,
        int $id,
        int $minWorkspaceId = 0,
        int $maxWorkspaceId = PHP_INT_MAX,
    ) {
        $this->warmupProject($limesurveyDataProvider, Project::findOne(['id' => $id]), $minWorkspaceId, $maxWorkspaceId);
    }

    public function actionWarmupWorkspace(
        LimesurveyDataProvider $limesurveyDataProvider,
        int $id
    ) {
        $this->warmupWorkspace(WorkspaceForLimesurvey::findOne(['id' => $id]), $limesurveyDataProvider);
    }

    protected function warmupProject(
        LimesurveyDataProvider $limesurveyDataProvider,
        Project $project,
        int $minWorkspaceId = 0,
        int $maxWorkspaceId = PHP_INT_MAX
    ) {
        /** @var WorkspaceForLimesurvey $workspace */
        foreach (
            $project->getWorkspaces()
                     ->orderBy('id')
                     ->andWhere(['>=', 'id', $minWorkspaceId])
                     ->andWhere(['<=', 'id', $maxWorkspaceId])
                     ->each() as $workspace
        ) {
            $this->warmupWorkspace($workspace, $limesurveyDataProvider);
        }
    }

    public function actionWarmupEmptyWorkspaces(LimesurveyDataProvider $limesurveyDataProvider): void
    {
        $query = WorkspaceForLimesurvey::find()
            ->andWhere(['not', [
                'id' => ResponseForLimesurvey::find()->select('workspace_id')->distinct()
            ]]);
        foreach ($query->each() as $workspace) {
            $this->warmupWorkspace($workspace, $limesurveyDataProvider);
        }
    }

    public function actionWarmupOldestWorkspaces(LimesurveyDataProvider $limesurveyDataProvider): void
    {
        $workspaceIds = ResponseForLimesurvey::find()
            ->groupBy('workspace_id')
            ->orderBy('min(last_updated)', 'workspace_id')
            ->select('workspace_id')
            ->limit(100)
            ->column();
        foreach (WorkspaceForLimesurvey::find()->andWhere(['id' => $workspaceIds])->each() as $workspace) {
            $this->warmupWorkspace($workspace, $limesurveyDataProvider);
        }
    }

    private function warmupWorkspace(WorkspaceForLimesurvey $workspace, LimesurveyDataProvider $limesurveyDataProvider)
    {
        $loader = new LimesurveyDataLoader();
        $token = $workspace->getAttribute('token');
        $this->stdout("Starting cache warmup for workspace [{$workspace->id}] {$workspace->title}..\n", Console::FG_CYAN);
        $this->stdout("Checking responses for workspace {$workspace->title}..", Console::FG_CYAN);
        $ids = [];
        foreach ($limesurveyDataProvider->refreshResponsesByToken($workspace->project->base_survey_eid, $workspace->getAttribute('token')) as $response) {
            $key = [
                'id' => $response->getId(),
                'survey_id' => $response->getSurveyId()
            ];
            /**
             * @var ResponseForLimesurvey $responseModel
             */
            $responseModel = ResponseForLimesurvey::findOne($key) ?? new ResponseForLimesurvey($key);
            $loader->loadData($response->getData(), $workspace, $responseModel);
            if ($responseModel->isNewRecord) {
                $this->stdout($responseModel->save() ? '+' : '-', Console::FG_RED);
            } elseif (empty($responseModel->dirtyAttributes)) {
                $responseModel->save();
                $this->stdout('0', Console::FG_GREEN);
            } else {
                $this->stdout($responseModel->save() ? '+' : '-', Console::FG_YELLOW);
            }
            $ids[] = $response->getId();
        }
        // Remove old records
        ResponseForLimesurvey::deleteAll([
            'and',
            [
                'survey_id' => $workspace->project->base_survey_eid,
                'workspace_id' => $workspace->id,
            ],
            ['not in', 'id', $ids],

        ]);

        $this->stdout("OK\n", Console::FG_GREEN);
    }
}
