<?php

use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use yii\widgets\Menu;
use kartik\helpers\Html;

/**
 * @var \yii\web\View $this
 */

$visible = false;

if (isset($this->params['subMenu'], $this->params['subMenu']['items'])) {
    foreach($this->params['subMenu']['items'] as $item) {
        $visible = $visible || !isset($item['visible']) || $item['visible'];
    }
}


if($visible) {
    NavBar::begin([
        'renderInnerContainer' => true,
//        'innerContainerOptions' => [
//            'class' => 'container-fluid'
//        ],
        'options' => [
            'class' => 'navbar-default navbar-static-top',
            'style' => [
//                'margin-top' => '-20px',
//                    'top' => '70px',
//                'z-index' => 2000
            ]
        ],
    ]);

    echo Nav::widget(
        [
            'options' => ['class' => 'navbar-nav navbar-right'],
            'encodeLabels' => false,
            'items' => $this->params['subMenu']['items'],
        ]
    );
    NavBar::end();
}
?>