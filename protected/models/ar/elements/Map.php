<?php


namespace prime\models\ar\elements;

use prime\models\ar\Element;
use prime\widgets\element\Element as ElementWidget;
use prime\widgets\map\DashboardMap as MapWidget;
use SamIT\LimeSurvey\Interfaces\SurveyInterface;
use yii\validators\NumberValidator;

class Map extends Element
{
    public function getMarkerRadius(): ?int
    {
        return $this->getWidgetConfig()['markerRadius'] ?? null;
    }

    public function attributeLabels(): array
    {
        return array_merge(parent::attributeLabels(), [
            'markerRadius' => \Yii::t('app', 'Marker radius')
        ]);
    }

    public function setMarkerRadius($value)
    {
        $config = $this->config;
        if (empty($value)) {
            unset($config['markerRadius']);
        } else {
            $config['markerRadius'] = intval($value);
        }
        $this->config = $config;
    }
    protected function getWidgetInternal(
        SurveyInterface $survey,
        iterable $data
    ): ElementWidget {
        return new MapWidget($this, array_merge([
            'data' => $data,
            'survey' => $survey,
        ], $this->getWidgetConfig()));
    }

    public function rules(): array
    {
        $rules = parent::rules();
        $rules[] = [['markerRadius'], NumberValidator::class, 'min' => 1, 'max' => 100];
        return $rules;
    }
}
