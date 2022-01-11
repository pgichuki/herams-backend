<?php

declare(strict_types=1);

use prime\models\forms\survey\CreateForm;
use prime\models\forms\survey\UpdateForm;
use prime\widgets\Section;
use prime\widgets\surveyJs\Creator2 as Creator;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\JsExpression;
use yii\web\View;

/**
 * @var CreateForm|UpdateForm $model
 * @var View $this
 */

$this->title = $model instanceof CreateForm
    ? \Yii::t('app', 'Create survey')
    : \Yii::t('app', 'Update survey');

$this->registerCss(<<<CSS
.main,
.main .content {
    max-width: inherit;
    width: 100%;
}
CSS
);

Section::begin()
    ->withHeader($this->title);

$ajaxSaveUrl = Json::encode(Url::to(['survey/ajax-save']));
$surveyId = Json::encode($model instanceof UpdateForm ? $model->getSurveyId() : null);
echo Creator::widget([
    'options' => [],
    'surveyCreatorCustomizers' => [
        new JsExpression('(creator) => { creator.toolbox.allowExpandMultipleCategories = true}'),
        new JsExpression(<<<JS
function(surveyCreator) {
  let surveyId = {$surveyId};
  surveyCreator.saveSurveyFunc = function (saveNo, callback) {
    const ajaxSaveUrl = {$ajaxSaveUrl} + (surveyId != null ? '?id=' + surveyId : '');
    
    const response = fetch(
      ajaxSaveUrl,
      {
        method: 'POST',
        mode: 'cors',
        cache: 'no-cache',
        headers: {
          'Accept': 'application/json;indent=2',
          'Content-Type': 'application/json',
          'X-CSRF-Token': yii.getCsrfToken(),
        },
        body: JSON.stringify({config: surveyCreator.JSON}),
      }
    )
    .then(response => response.json())
    .then(data => {callback(saveNo, true);surveyId = data.id})
  } 
}
JS
        ),
    ],
    'survey' => $model->config,
]);

Section::end();
