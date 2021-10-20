<?php

declare(strict_types=1);

use app\components\ActiveForm;
use prime\components\View;
use prime\interfaces\WorkspaceForTabMenu;
use prime\models\ar\WorkspaceForLimesurvey;
use prime\models\forms\Share;
use prime\widgets\menu\WorkspaceTabMenu;
use prime\widgets\Section;

/**
 * @var WorkspaceForLimesurvey $workspace
 * @var Share $model
 * @var WorkspaceForTabMenu $tabMenuModel
 * @var View $this
 */

$this->title = $workspace->title;

$this->beginBlock('tabs');
echo WorkspaceTabMenu::widget([
    'workspace' => $tabMenuModel,
]);
$this->endBlock();

Section::begin(['header' => \Yii::t('app', 'Add new user')]);
$form = ActiveForm::begin([
    'method' => 'POST',
    "type" => ActiveForm::TYPE_HORIZONTAL,
    'formConfig' => [
        'labelSpan' => 3
    ]
]);

echo $model->renderForm($form);
$form->end();
Section::end();
Section::begin(['header' => \Yii::t('app', 'View user permissions')]);
echo $model->renderTable();
Section::end();
