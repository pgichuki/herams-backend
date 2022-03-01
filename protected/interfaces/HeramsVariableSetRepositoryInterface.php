<?php

declare(strict_types=1);

namespace prime\interfaces;

use prime\helpers\HeramsVariableSet;
use prime\values\ProjectId;

interface HeramsVariableSetRepositoryInterface
{
    public function retrieveForProject(ProjectId $projectId): HeramsVariableSet;
}
