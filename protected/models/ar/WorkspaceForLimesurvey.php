<?php
declare(strict_types=1);

namespace prime\models\ar;

use prime\components\ActiveQuery;
use prime\components\LimesurveyDataProvider;
use prime\helpers\ArrayHelper;
use prime\models\forms\ResponseFilter;
use prime\objects\HeramsCodeMap;
use SamIT\LimeSurvey\Interfaces\WritableTokenInterface;
use SamIT\Yii2\VirtualFields\VirtualFieldBehavior;
use yii\db\Expression;
use yii\db\Query;
use yii\validators\UniqueValidator;

/**
 * This version of the workspace is separated since it is the "old" version.
 * When we transition to only use SurveyJS this class should have no usages anymore and can be deleted.
 *
 * Attributes
 * @property string $token
 */
class WorkspaceForLimesurvey extends Workspace
{
    /**
     * @var WritableTokenInterface
     */
    protected $_token;

    public static function find(): ActiveQuery
    {
        return parent::find()->andWhere(['not', ['token' => null]]);
    }

    public function isTransactional($operation)
    {
        return true;
    }

    public static function labels(): array
    {
        return array_merge(parent::labels(), [
            'closed_at' => \Yii::t('app.model.workspace', 'Closed at'),
            'latestUpdate' => \Yii::t('app.model.workspace', 'Latest update'),
            'token' => \Yii::t('app.model.workspace', 'Token'),
            'contributorCount' => \Yii::t('app.model.workspace', 'Contributors'),
            'facilityCount' => \Yii::t('app.model.workspace', 'Facilities'),
            'responseCount' => \Yii::t('app.model.workspace', 'Responses')
        ]);
    }

    public function rules(): array
    {
        return ArrayHelper::merge(
            parent::rules(),
            [
                [['token'], UniqueValidator::class, 'filter' => function (Query $query) {
                    $query->andWhere(['project_id' => $this->project_id]);
                }],
            ]
        );
    }

    public function beforeSave($insert)
    {
        $result = parent::beforeSave($insert);
        if ($result && empty($this->token)) {
            // Attempt creation of a token.
            $token = $this->getLimesurveyDataProvider()->createToken($this->project->base_survey_eid, app()->security->generateRandomString(15));

            $token->setValidFrom(null);
            $this->_token = $token;
            $this->setAttribute('token', $token->getToken());
            return $token->save();
        }
        return $result;
    }

    public function getLimesurveyDataProvider(): LimesurveyDataProvider
    {
        return \Yii::$app->get('limesurveyDataProvider');
    }

    public function getSurveyUrl(bool $canWrite = false, ?bool $canDelete = null): string
    {
        return $this->getLimesurveyDataProvider()->getUrl(
            $this->project->base_survey_eid,
            [
                'token' => $this->getAttribute('token'),
                'newtest' => 'Y',
                'lang' => \Yii::$app->language,
                'createButton' => 0,
                'seamless' => 1,
                'deleteButton' => $canDelete ?? $canWrite,
                'editButton' => $canWrite,
                'copyButton' => $canWrite
            ]
        );
    }

    public function setProjectId(int $id): void
    {
        $this->project_id = $id;
    }
}
