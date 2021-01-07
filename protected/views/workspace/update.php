<?php

use app\components\Form;
use app\components\ActiveForm;
use yii\bootstrap\Html;
use yii\helpers\Url;
use prime\models\ar\Permission;
use prime\helpers\Icon;
use prime\widgets\menu\WorkspaceTabMenu;

/**
 * @var  \prime\components\View $this
 * @var \prime\models\ar\Workspace $model
 */
assert($this instanceof \prime\components\View);
assert($model instanceof \prime\models\ar\Workspace);


$this->params['breadcrumbs'][] = [
    'label' => $model->project->title,
    'url' => ['project/workspaces', 'id' => $model->project->id]
];
$this->params['breadcrumbs'][] = [
    'label' => \Yii::t('app', "Workspace {workspace}", [
        'workspace' => $model->title,
    ]),
    'url' => ['workspace/limesurvey', 'id' => $model->id]
];
$this->title = \Yii::t('app', "Workspace {workspace}", [
    'workspace' => $model->title,
]);


echo WorkspaceTabMenu::widget([
    'workspace' => $model,
    'currentPage' => $this->context->action->uniqueId
]);

echo Html::beginTag('div', ['class' => "content"]);
echo Html::beginTag('div', ['class' => 'action-group']);

if (app()->user->can(Permission::PERMISSION_DELETE, $model)) {
    echo Html::a(
        Icon::trash() . \Yii::t('app', 'Delete'),
        ['workspace/delete', 'id' => $model->id],
        [
            'data-method' => 'delete',
            'title' => \Yii::t('app', 'Delete'),
            'data-confirm' => \Yii::t('app', 'Are you sure you wish to remove this workspace from the system?'),
            'class' => 'btn btn-delete btn-icon'
        ]
    );
}
echo Html::endTag('div');

?>

<div class="form-content form-bg">
    <h4><?= \Yii::t('app', 'Update Workspace') ?></h4>
    <?php
    $form = ActiveForm::begin([
        'id' => 'update-project',
        'method' => 'PUT',
        "type" => ActiveForm::TYPE_HORIZONTAL,
        'formConfig' => [
            'showLabels' => true,
            'defaultPlaceholder' => false,
            'labelSpan' => 3
        ]
    ]);

    echo \app\components\Form::widget([
        'form' => $form,
        'model' => $model,
        'columns' => 1,
        "attributes" => [
            'token' => [
                'type' => Form::INPUT_STATIC
            ],
            'title' => [
                'type' => Form::INPUT_TEXT,
            ],
        ]
    ]);
    echo \yii\bootstrap\ButtonGroup::widget([
        'options' => [
            'class' => [
                'pull-right'
            ],
        ],
        'buttons' => [
            Html::a(\Yii::t('app', 'Edit token'), ['workspace/configure', 'id' => $model->id], [
                'class' => ['btn btn-default']
            ]),
            Html::submitButton(\Yii::t('app', 'Save'), ['form' => 'update-project', 'class' => 'btn btn-primary']),

        ]
    ]);
    $form->end();
    ?>
</div>

<?php
echo Html::endTag('div');
?>