<?php

declare(strict_types=1);

namespace prime\tests\unit\models\page;

use Codeception\Test\Unit;
use prime\models\ar\Page;
use prime\models\pages\PageForBreadcrumb;
use prime\values\ProjectId;

/**
 * @covers \prime\models\pages\PageForBreadcrumb
 */
class PageForBreadcrumbTest extends Unit
{
    public function testConstructor(): void
    {
        $label = 'Page label';
        $pageId = 12345;
        $projectId = 23456;

        $page = new Page();
        $page->title = $label;
        $page->id = $pageId;
        $page->project_id = $projectId;

        $forBreadcrumb = new PageForBreadcrumb($page);
        $this->assertEquals(['/page/update', 'id' => $pageId], $forBreadcrumb->getUrl());
        $this->assertEquals(new ProjectId($projectId), $forBreadcrumb->getProjectId());
        $this->assertEquals($label, $forBreadcrumb->getLabel());
    }
}
