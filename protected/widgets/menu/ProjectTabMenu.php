<?php


namespace prime\widgets\menu;

use yii\base\Widget;
use prime\interfaces\PageInterface;
use prime\models\ar\Permission;
use yii\helpers\Html;
use \prime\models\ar\Project;

/**
 * Class Menu
 * Implements a tab menu for admin project pages
 * @package prime\widgets\menu
 */
class ProjectTabMenu extends TabMenu
{
    public Project $project;

    public function init()
    {
        parent::init();
        $this->permissionSubject = $this->project;
    }


    protected function renderMenu(): string
    {
        $this->tabs[] = [
            'url' => ['project/workspaces', 'id' => $this->project->id],
            'title' => \Yii::t('app', 'Workspaces') . " ({$this->project->workspaceCount})"
        ];

        $this->tabs[] = [
            'url' => ['project/pages', 'id' => $this->project->id],
            'permission' => Permission::PERMISSION_MANAGE_DASHBOARD,
            'title' => \Yii::t('app', 'Dashboard settings')
        ];
        $this->tabs[] = [
            'permission' => Permission::PERMISSION_WRITE,
            'url' => ['project/update', 'id' => $this->project->id],
            'title' => \Yii::t('app', 'Project settings')
        ];

        $this->tabs[] = [
            'url' => ['project/share', 'id' => $this->project->id],
            'title' => \Yii::t('app', 'Users'),
            'permission' => Permission::PERMISSION_SHARE
        ];

        $this->tabs[] = [
            'permission' => Permission::PERMISSION_SURVEY_BACKEND,
            'url' => ['project/limesurvey', 'id' => $this->project->id],
            'title' => \Yii::t('app', 'Backend administration')
        ];


        return parent::renderMenu();
    }
}
