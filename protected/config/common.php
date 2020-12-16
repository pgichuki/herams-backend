<?php
declare(strict_types=1);

use prime\components\JwtSso;
use prime\modules\Api\models\Key;
use SamIT\abac\interfaces\Environment;
use SamIT\abac\interfaces\PermissionRepository;
use SamIT\abac\interfaces\Resolver;
use SamIT\abac\interfaces\RuleEngine;
use SamIT\abac\repositories\PreloadingSourceRepository;
use SamIT\abac\values\Authorizable;
use SamIT\LimeSurvey\JsonRpc\Client;
use SamIT\LimeSurvey\JsonRpc\JsonRpcClient;
use SamIT\Yii2\abac\AccessChecker;
use SamIT\Yii2\abac\ActiveRecordResolver;
use yii\i18n\MissingTranslationEvent;
use yii\swiftmailer\Mailer;
use yii\web\User;

/** @var \prime\components\Environment|null $env */
assert(isset($env) && $env instanceof \prime\components\Environment);

require_once __DIR__ . '/../helpers/functions.php';
return [
    'id' => 'herams',
    'name' => 'HeRAMS',
    'basePath' => realpath(__DIR__ . '/../'),
    'runtimePath' => $env->get('RUNTIME_PATH', '/tmp'),
    'timeZone' => 'UTC',
    'vendorPath' => '@app/../vendor',
    'sourceLanguage' => 'en-US',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
        '@bower/bootstrap' => '@npm/bootstrap',
        '@prime' => '@app',
        '@views' => '@app/views',
        '@tests' => '@app/../tests',
    ],
    'bootstrap' => [
        'log',

    ],
    'components' => [
        'db' => [
            'class' => \yii\db\Connection::class,
            'charset' => 'utf8mb4',
            'dsn' => 'mysql:host=' . $env->get('DB_HOST') . ';port=' . $env->get('DB_PORT', 3306) . ';dbname=' . $env->get('DB_NAME'),
            'password' => $env->getSecret('database/password'),
            'username' => $env->getSecret('database/username'),
            'enableSchemaCache' => !YII_DEBUG,
            'schemaCache' => 'cache',
            'enableQueryCache' => true,
            'queryCache' => 'cache',
            'tablePrefix' => 'prime2_'
        ],
        'limesurveySSo' => [
            'class' => JwtSso::class,
            'errorRoute' => ['site/lime-survey'],
            'privateKey' => $env->getSecret('limesurvey/sso_private_key'),
            'loginUrl' => 'https://ls.herams.org/plugins/unsecure?plugin=FederatedLogin&function=SSO',
            'userNameGenerator' => function ($id) use ($env) {
                return $env->get('SSO_PREFIX', 'prime_') . $id;
            }
        ],
        'urlSigner' => [
            'class' => \SamIT\Yii2\UrlSigner\UrlSigner::class,
            'secret' => $env->getSecret('app/url_signer_secret'),
            'hmacParam' => 'h',
            'paramsParam' => 'p',
            'expirationParam' => 'e'
        ],
        'preloadingSourceRepository' => PreloadingSourceRepository::class,
        'abacManager' => static function (
            Resolver $resolver, // Taken from container
            RuleEngine $engine, // Taken from container
            PermissionRepository $preloadingSourceRepository  // Taken from app
        ) {
            $environment = new class extends ArrayObject implements Environment {
            };
            $environment['globalAuthorizable'] = new Authorizable(AccessChecker::GLOBAL, AccessChecker::BUILTIN);
            return new \SamIT\abac\AuthManager($engine, $preloadingSourceRepository, $resolver, $environment);
        },
        'authManager' => static function (\SamIT\abac\AuthManager $abacManager) {
            return new \prime\components\AuthManager($abacManager, [
                'userClass' => \prime\models\ar\User::class,
                'globalId' => AccessChecker::GLOBAL,
                'globalName' => AccessChecker::BUILTIN,
                'guestName' => AccessChecker::BUILTIN,
                'guestId' => AccessChecker::GUEST,
            ]);
        },
        'check' => static function (User $user) {
            return new \prime\helpers\AccessCheck($user);
        },


        'limesurveyCache' => [
            'class' => \yii\caching\FileCache::class,
            'cachePath' => '@runtime/limesurveyCache'
        ],
        'cache' => [
            'class' => \yii\caching\FileCache::class
        ],
//        'formatter' => [
//            'numberFormatterOptions' => [
//                NumberFormatter::MIN_FRACTION_DIGITS => 0,
//                NumberFormatter::MAX_FRACTION_DIGITS => 2,
//            ]
//
//        ],
        'limesurveyDataProvider' => [
            'class' => \prime\components\LimesurveyDataProvider::class,
            'client' => 'limesurvey',
        ],
        'limesurvey' => function () use ($env) {
            $json = new JsonRpcClient($env->get('LS_HOST'), false, 30);
            $result = new Client($json, $env->getSecret('limesurvey/username'), $env->getSecret('limesurvey/password'));
            $result->setCache(function ($key, $value, $duration) {
                \Yii::info('Setting cache key: ' . $key, 'ls');
                // Ignore hardcoded duration passed in downstream library
                return app()->get('limesurveyCache')->set($key, $value, 6 * 3600);
            }, function ($key) {
                $result = app()->get('limesurveyCache')->get($key);
                if ($result === false) {
                    \Yii::info('Getting MISS key: ' . $key, 'ls');
                } else {
                    \Yii::info('Getting HIT key: ' . $key, 'ls');
                }
                return $result;
            });
            return $result;
        },
        'log' => [
            'flushInterval' => 1,
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'exportInterval' => 1,
                    'class' => \yii\log\FileTarget::class,
                    'levels' => ['error', 'warning'],
                    'logFile' => '@runtime/logs/error.log'
                ]
            ]
        ],
        'user' => [
            'class' => \yii\web\User::class,
            'loginUrl' => '/session/create',
            'identityClass' => \prime\models\ar\User::class,
            'on ' . \yii\web\User::EVENT_AFTER_LOGIN => function (\yii\web\UserEvent $event) {
                if (isset($event->identity->language)) {
                    \Yii::$app->language = $event->identity->language;
                }
            }
        ],
        'i18n' => [
            'class' => \yii\i18n\I18N::class,
            'translations' => [
                'app*' => [
                    'class' => \yii\i18n\GettextMessageSource::class,
                    'useMoFile' => false,
                    'basePath' => '@vendor/herams/i18n/locales',
                    'catalog' => 'LC_MESSAGES/app',
                    'on ' . \yii\i18n\MessageSource::EVENT_MISSING_TRANSLATION => static function (MissingTranslationEvent $event) {
                        if (YII_DEBUG) {
                            $event->translatedMessage = "@MISSING: {$event->category}.{$event->message} FOR LANGUAGE {$event->language} @";
                        }
                    }
                ],
            ]
        ],
        'mailer' => [
            'class' => Mailer::class,
            'messageConfig' => [
                'from' => [$env->get('MAIL_FROM', 'support@herams.org') => 'HeRAMS Support']
            ],
            'transport' => [
                'class' => Swift_SmtpTransport::class,
                'username' => $env->getSecret('smtp/pass'),
                'password' => $env->getSecret('smtp/pass'),
                'constructArgs' => [
                    $env->get('SMTP_HOST'),
                    $env->get('SMTP_PORT'),
                    $env->get('SMTP_ENCRYPTION')
                ]
            ]
        ],
    ],
    'modules' => [
        'api' => [
            'class' => \prime\modules\Api\Module::class,
            'components' => [
                'user' => [
                    'class' => \yii\web\User::class,
                    'enableSession' => false,
                    'identityClass'=> Key::class
                ]
            ]
        ]
    ],
    'params' => [
        'languages' => [
            'en-US',
            'ar',
            'nl-NL',
            'fr-FR'
        ],
        'defaultSettings' => [
            'icons.globalMonitor' => 'globe',
            'icons.projects' => 'tasks',
            'icons.reports' => 'file',
            'icons.preview' => 'file',
            'icons.userLists' => 'bullhorn',
            'icons.user' => 'user',
            'icons.configuration' => 'wrench',
            'icons.logIn' => 'log-in',
            'icons.logOut' => 'log-out',
            'icons.search' => 'search',
            'icons.read' => 'eye-open',
            'icons.update' => 'cog',
            'icons.share' => 'share',
            'icons.close' => 'stop',
            'icons.open' => 'play',
            'icons.remove' => 'trash',
            'icons.request' => 'forward',
            'icons.limeSurveyUpdate' => 'pencil',
            'icons.requestAccess' => 'info-sign'
        ],
        'responseSubmissionKey' => $env->getSecret('limesurvey/response_submission_key')
    ]
];
