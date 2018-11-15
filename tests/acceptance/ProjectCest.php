<?php

use \Step\Acceptance\User;
use \Step\Acceptance\Admin;

class ProjectCest
{
    public function _before(AcceptanceTester $I)
    {
    }

    public function _after(AcceptanceTester $I)
    {
    }

    // tests

    public function testShareProject(User $I)
    {
        return;
        $tool = new \prime\models\ar\Tool();
        $tool->id = 1;
        $tool->acronym = 'TEST';

        if (!$tool->save(false)) {
            codecept_debug("Couldn't create tool for testing");
            throw new \Exception("Couldn't create tool for testing");
        }
        $project = new \prime\models\ar\Project();
        $project->id = 1;
        $project->owner_id = 9;
        $project->title = 'Test sharing';
        $project->country_iso_3 = 'NLD';
        $project->tool_id = 1;
        return;
        if (!$project->save(false)) {
            $I->comment("Project for testing NOT created.");
//            $I->fa
        } else {
            $I->comment("Project for testing created.");
        }
        $I->amOnPage('/projects');
        $I->waitForText('Test sharing', 10);
        $I->see('Test sharing');
        $I->click('[title=Share]', 'tr[data-key=1]');

        $I->seeCurrentUrlEquals('/projects/share?id=1');
        $I->see('Already shared with');
        $I->selectOption('Users', "Test User (test@localhost.net)");
        $I->selectOption('Permission', "Read");
        $I->click('Share', 'button');
        $I->seeInSource('has been shared');
        $I->see('Test User', 'tr');
    }

    public function testCreateProject(Admin $I)
    {
        $I->amOnPage('/projects');
        $I->click(['link' => 'Create']);
        $I->wait(1);
        $I->seeCurrentUrlEquals('/projects/create');

        $I->fillField('Title', "My new project");
        $I->fillHtmlField(['css' => '[name*=description]'], "desc");
        $I->selectOption('Tool', 'Cluster Description');
//        $I->selectOption('Survey', )


    }
}