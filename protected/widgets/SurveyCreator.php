<?php

declare(strict_types=1);

namespace prime\widgets;

use prime\assets\KnockoutBundle;
use prime\assets\SurveyJsCreatorBundle;
use yii\base\Widget;
use yii\helpers\Html;

class SurveyCreator extends Widget
{
    private string $tag = 'survey-creator-test';
    public array $options = [];

    public function init(): void
    {
        parent::init();

        $options = $this->options;
        $options['id'] = 'survey-creator';
        ob_start();
        echo Html::beginTag($this->tag, $options);
    }

    public function run(): string
    {
        $this->view->registerJsFile('https://surveyjs.io/DevBuilds/survey-core/survey.core.js', ['depends' => KnockoutBundle::class]);
        $this->view->registerJsFile('https://surveyjs.io/DevBuilds/survey-knockout-ui/survey-knockout-ui.min.js', ['depends' => KnockoutBundle::class]);
        $this->view->registerJsFile('https://surveyjs.io/DevBuilds/survey-creator-knockout/survey-creator-knockout.min.js', ['depends' => KnockoutBundle::class]);
        $css = <<<CSS

CSS;

        $this->view->registerCss($css);
        $this->view->registerJsFile('/js/components/survey-creator.js', ['type' => 'module']);


        echo Html::endTag($this->tag);
        return ob_get_clean();
    }
}
