<?php

namespace prime\tests\functional\controllers\user;

use Carbon\Carbon;
use prime\models\ar\Permission;
use prime\models\ar\Project;
use prime\tests\FunctionalTester;
use SamIT\abac\interfaces\Resolver;
use SamIT\Yii2\UrlSigner\UrlSigner;

/**
 * @covers \prime\controllers\user\AcceptInvitation
 * @covers \prime\controllers\UserController
 * @covers \prime\models\forms\user\AcceptInvitationForm
 */
class AcceptInvitationCest
{
    private function getSignedUrl(
        string $email,
        Project $project,
        array $permissions = [Permission::PERMISSION_READ],
    ) {
        /** @var UrlSigner $urlSigner */
        $urlSigner = \Yii::$app->urlSigner;
        $resolver = \Yii::createObject(Resolver::class);
        $subject = $resolver->fromSubject($project);

        $url = [
            '/user/accept-invitation',
            'email' => $email,
            'subject' => $subject->getAuthName(),
            'subjectId' => $subject->getId(),
            'permissions' => implode(',', $permissions),
        ];
        $urlSigner->signParams($url, false, Carbon::now()->addDays(7));
        return $url;
    }

    public function testInvitationLinkChangedEmail(FunctionalTester $I)
    {
        $project = $I->haveProject();
        $email = 'email@test.com';
        $url = $this->getSignedUrl($email, $project);

        $I->amOnPage($url);
        $I->seeResponseCodeIs(200);

        $I->fillField('Email', 'changed' . $email);
        $I->click('Create account');
        $I->seeResponseCodeIs(200);

        $I->seeEmailIsSent();
    }

    public function testInvitationLinkLoggedIn(FunctionalTester $I)
    {
        $page = $I->havePage();
        $email = 'email@test.com';
        $url = $this->getSignedUrl($email, $page->project);

        $I->amOnPage($url);
        $I->seeResponseCodeIs(200);
        $I->dontSee('Accept invitation');

        $I->amLoggedInAs(TEST_USER_ID);
        $I->amOnPage($url);
        $I->seeResponseCodeIs(200);

        $I->click('Accept invitation');
        $I->seeResponseCodeIs(200);

        $I->amOnPage(['project/view', 'id' => $page->project_id]);
        $I->seeResponseCodeIs(200);
    }

    public function testInvitationLinkSameEmail(FunctionalTester $I)
    {
        $project = $I->haveProject();
        $email = 'email@test.com';
        $url = $this->getSignedUrl($email, $project);

        $I->amOnPage($url);
        $I->seeResponseCodeIs(200);

        $I->stopFollowingRedirects();
        $I->click('Create account');
        $I->canSeeResponseCodeIsRedirection();
        $I->dontSeeEmailIsSent();
    }

    public function testInvitationLinkSingleUse(FunctionalTester $I)
    {
        $project = $I->haveProject();
        $email = 'email@test.com';
        $url = $this->getSignedUrl($email, $project);

        $I->amLoggedInAs(TEST_USER_ID);
        $I->amOnPage($url);
        $I->seeResponseCodeIs(200);

        $I->click('Accept invitation');
        $I->seeResponseCodeIs(200);

        $I->amOnPage($url);
        $I->seeResponseCodeIs(403);
    }
}
