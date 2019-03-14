<?php


namespace prime\widgets\map;


use prime\objects\HeramsResponse;
use prime\traits\SurveyHelper;
use SamIT\LimeSurvey\Interfaces\SurveyInterface;
use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\JsExpression;

class DashboardMap extends Widget
{
    use SurveyHelper;
    public const TILE_LAYER = 'tileLayer';
    public $baseLayers = [
        [
            "type" => DashboardMap::TILE_LAYER,
            "url" => "https://services.arcgisonline.com/arcgis/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}",
        ]
    ];

    public $options = [
        'class' => ['map']
    ];

    public $center = [8.6753, 9.0820];
    public $zoom = 5.4;

    /**
     * @var HeramsResponse[]
     */
    public $data = [];
    /** @var SurveyInterface */
    public $survey;

    public $colors;

    public $code;

    public function init()
    {
        $this->colors = new JsExpression('chroma.brewer.OrRd');
        parent::init();
    }


    private function getCollections(iterable $data)
    {
        try {
            $types = $this->getAnswers($this->code);
            $getter = function($response) {
                return $response->getValueForCode($this->code);
            };
        } catch (\InvalidArgumentException $e) {
            $types = [];
            $getter = function($response) {
                $getter = 'get' . ucfirst($this->code);
                return $response->$getter();
            };
        }

        $collections = [];
        /** @var HeramsResponse $response */
        foreach($data as $response) {
            $value = $getter($response);
            $latitude = $response->getLatitude();
            $longitude = $response->getLongitude();
            if (abs($latitude) < 0.0000001
                && abs($longitude) < 0.0000001) {
                continue;
            }

            if (!isset($collections[$value])) {
                $collections[$value] = [
                    "type" => "FeatureCollection",
                    'features' => [],
                    "title" => $types[$value] ?? $value ?? 'Unknown',
                ];
            }

            $point = [
                "type" => "Feature",
                "geometry" => [
                    "type" => "Point",
                    "coordinates" => [$longitude, $latitude]
                ],
                "properties" => [
                    'title' => $response->getName(),
                ]

//                'subtitle' => '',
//                'items' => [
//                    'ownership',
//                    'building damage',
//                    'functionality'
//                ]
            ];
            $collections[$value]['features'][] = $point;
        }
        uksort($collections, function($a, $b) {
            if ($a === "" || $a === "-oth-") {
                return 1;
            } elseif ($b === "" || $b === "-oth-") {
                return -1;
            }
            return $a <=> $b;
        });
        return array_values($collections);
    }

    public function run()
    {
        $this->registerClientScript();
        $options = $this->options;
        Html::addCssClass($options, strtr(__CLASS__, ['\\' => '_']));
        $options['id'] = $this->getId();
        echo Html::beginTag('div', $options);
        $id = Json::encode($this->getId());

        $config = Json::encode([
            'preferCanvas' => true,
            'center' => $this->center,
            'zoom' => $this->zoom
        ]);

        $baseLayers = Json::encode($this->baseLayers);
        $data = Json::encode($this->getCollections($this->data));

        $scale = Json::encode($this->colors);
        $this->view->registerJs(<<<JS
        (function() {
            let map = L.map($id, $config);
            for (let baseLayer of $baseLayers) {
                switch (baseLayer.type) {
                    case 'tileLayer':
                        L.tileLayer(baseLayer.url, baseLayer.options || {}).addTo(map);
                        break;
                }
            }
            let bounds = [];
            let data = $data;
                let layers = {};
                let scale = chroma.scale($scale).colors(data.length);
                for (let set of data) {
                    let color = scale.pop();
                    let layer = L.geoJSON(set.features, {
                        pointToLayer: function(feature, latlng) {
                            bounds.push(latlng);
                            return L.circleMarker(latlng, {
                                radius: 2,
                                color: color,
                                weight: 1,
                                opacity: 1,
                                fillOpacity: 0.8
                            });
                        }
                    });
                    layer.bindTooltip(function(e) {
                        return e.feature.properties.title;
                    }),
                    layer.bindPopup(function(e) {
                        return e.feature.properties.title;
                    });
                    layer.addTo(map);
                    
                    let legend = document.createElement('span');
                    legend.classList.add('legend');
                    legend.style.setProperty('--color', color);
                    legend.title = set.features.length;
                    //legend.attributeStyleMap.set('--color', color);
                    legend.textContent = set.title;
                    
                    // legend.css
                    layers[legend.outerHTML] = layer;
                }
                L.control.layers([], layers, {
                    collapsed: false
                }).addTo(map);
                
                
                L.control.scale({
                    metric: true,
                    imperial: false
                }).addTo(map);
                map.fitBounds(bounds, {
                    padding: [50, 50]
                });
        })();

JS
        );

        echo Html::endTag('div');
    }


    protected function registerClientScript()
    {
        $this->view->registerAssetBundle(MapBundle::class);
//        $config = [
//
//        ]
    }

}