<?php
    /** @var User $user */
    /** @var array $resetRoute */

use prime\models\ar\User;
use yii\helpers\Url;

?>
<h1>HeRAMS password reset</h1>
Someone requested a password reset for your HeRAMS account.
If this was you please click <a href="<?= Url::to($resetRoute, true); ?>">here</a>, this link will expire in 4 hours.