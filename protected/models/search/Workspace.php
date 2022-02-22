<?php

declare(strict_types=1);

namespace prime\models\search;

use prime\models\ar\Favorite;
use prime\models\ar\Project;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\data\DataProviderInterface;
use yii\data\Sort;
use yii\db\Expression;
use yii\validators\BooleanValidator;
use yii\validators\NumberValidator;
use yii\validators\SafeValidator;
use yii\validators\StringValidator;

class Workspace extends Model
{
    public $created_at;
    public $favorite;
    public $id;
    private Project $project;
    public $title;
    private \prime\models\ar\User $user;

    public function __construct(
        Project $project,
        \prime\models\ar\User $user,
        array $config = []
    ) {
        parent::__construct($config);
        $this->project = $project;
        $this->user = $user;
    }

    public function rules(): array
    {
        return [
            [['created_at'], SafeValidator::class],
            [['title'], StringValidator::class],
            [['id'], NumberValidator::class],
            [['favorite'], BooleanValidator::class]
        ];
    }

    public function search($params): DataProviderInterface
    {
        $query = \prime\models\ar\Workspace::find();

        $query->with('project');
        $query->withFields('latestUpdate', 'facilityCount', 'responseCount', 'contributorCount');
        $query->andFilterWhere(['project_id' => $this->project->id]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'id' => 'workspace-data-provider',
            'pagination' => [
                'pageSize' => 10
            ]
        ]);

        // We are forced to do it this way because Yii doesn't properly bind query params from the order by clause.
        $favorites = Favorite::find()->workspaces()->user($this->user)->select('target_id')->createCommand()->rawSql;

        $sort = new Sort([
            'attributes' => [
                'id',
                'title',
                'created_at',
                'permissionCount',
                'facilityCount',
                'responseCount',
                'contributorCount',
                'latestUpdate' => [
                    'asc' => [
                        new Expression('[[latestUpdate]] IS NOT NULL ASC'),
                        'latestUpdate' => SORT_ASC
                    ],
                    'desc' => [
                        'latestUpdate' => SORT_DESC
                    ],
                    'default' => SORT_DESC,
                ],
                'favorite' => [
                    'asc' => new Expression("[[id]] IN ($favorites)"),
                    'desc' => new Expression("[[id]] NOT IN ($favorites)"),
                    'default' => SORT_DESC,
                ]
            ],
            'defaultOrder' => [
                'favorite' => SORT_DESC,
                'latestUpdate' => SORT_DESC
            ]
        ]);

        $dataProvider->setSort($sort);
        if (!$this->load($params) || !$this->validate()) {
            return $dataProvider;
        }

        if (isset($this->created_at)) {
            $interval = explode(' - ', $this->created_at);
            if (count($interval) == 2) {
                $query->andFilterWhere([
                    'and',
                    ['>=', 'created_at', $interval[0]],
                    ['<=', 'created_at', $interval[1] . ' 23:59:59']
                ]);
            }
        }

        if ($this->favorite !== "") {
            $condition = ['id' => $this->user->getFavorites()->workspaces()->select('target_id')];
            if ($this->favorite) {
                $query->andWhere($condition);
            } else {
                $query->andWhere(['not', $condition]);
            }
        }
        $query->andFilterWhere(['like', 'title', trim($this->title)]);
        $query->andFilterWhere(['id' => $this->id]);
        return $dataProvider;
    }
}
