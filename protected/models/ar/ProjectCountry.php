<?php

namespace prime\models\ar;

use prime\components\ActiveRecord;
use prime\models\Country;

class ProjectCountry extends ActiveRecord
{
    public function getCountry()
    {
        return Country::findOne($this->country_iso_3);
    }

    public function getProject()
    {
        return $this->hasOne(Project::class, ['id' => 'project_id']);
    }
}