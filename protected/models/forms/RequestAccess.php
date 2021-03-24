<?php
declare(strict_types=1);

namespace prime\models\forms;

use prime\models\ar\AccessRequest;
use yii\base\Model;
use yii\validators\RangeValidator;
use yii\validators\RequiredValidator;
use yii\validators\StringValidator;
use yii\web\ServerErrorHttpException;

/**
 * Class RequestAccess
 * @package prime\models\forms
 */
class RequestAccess extends Model
{
    public string $body = '';
    public array $permissions = [];
    public string $subject = '';

    public function __construct(
        private object $model,
        private array $permissionOptions,
        $config = []
    ) {
        parent::__construct($config);
    }

    public function createRecords(): void
    {
        $accessRequest = new AccessRequest();
        $accessRequest->body = $this->body;
        $accessRequest->subject = $this->subject;
        $accessRequest->target_class = get_class($this->model);
        $accessRequest->target_id = $this->model->id;
        if (!$accessRequest->save()) {
            throw new ServerErrorHttpException('Failed saving the record.');
        }
    }

    public function getPermissionOptions(): array
    {
        return $this->permissionOptions;
    }

    public function rules(): array
    {
        return [
            [['body', 'permissions', 'subject'], RequiredValidator::class],
            [['body', 'subject'], StringValidator::class],
            [['permissions'], RangeValidator::class, 'range' => array_keys($this->permissionOptions), 'allowArray' => true],
        ];
    }
}
