<?php

declare(strict_types=1);

namespace prime\tests\unit\models\workspace;

use Codeception\Test\Unit;
use prime\models\ar\WorkspaceForLimesurvey;
use prime\models\workspace\WorkspaceForBreadcrumb;
use prime\values\ProjectId;

/**
 * @covers \prime\models\workspace\WorkspaceForBreadcrumb
 */
class WorkspaceForBreadcrumbTest extends Unit
{
    public function testConstructor(): void
    {
        $label = 'Project label';
        $projectId = 23456;
        $workspaceId = 12345;

        $workspace = new WorkspaceForLimesurvey();
        $workspace->id = $workspaceId;
        $workspace->title = $label;
        $workspace->project_id = $projectId;

        $forBreadcrumb = new WorkspaceForBreadcrumb($workspace);

        $this->assertEquals(['/workspace/facilities', 'id' => $workspaceId], $forBreadcrumb->getUrl());
        $this->assertEquals(new ProjectId($projectId), $forBreadcrumb->getProjectId());
        $this->assertEquals($label, $forBreadcrumb->getLabel());

    }
}
