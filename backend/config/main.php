<?php

$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php',
    require __DIR__ . '/params.php',
    require __DIR__ . '/params-local.php'
);

return [
    'id' => 'app-backend',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'backend\controllers',
    'bootstrap' => ['log'],
    'modules' => [],
    'components' => [
        'request' => [
            'csrfParam' => '_csrf-backend',
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ],
        ],
        'authManager' => [
            'class' => 'yii\rbac\DbManager',
        ],
        'user' => [
            'identityClass' => 'common\models\User',
            'enableAutoLogin' => true,
            'identityCookie' => ['name' => '_identity-backend', 'httpOnly' => true],
        ],
        'session' => [
            // this is the name of the session cookie used for login on the backend
            'name' => 'advanced-backend',
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => \yii\log\FileTarget::class,
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],

        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'enableStrictParsing' => false,
            'rules' => [
            ],
        ],

        'response' => [
            'class' => 'yii\web\Response',
            'on beforeSend' => function ($event) {
                $response = $event->sender;
                if ($response->format == 'html') {
                    return $response;
                }

                $responseData = $response->data;

                if (is_string($responseData) && json_decode($responseData)) {
                    $responseData = json_decode($responseData, true);
                }

                if ($response->statusCode >= 200 && $response->statusCode <= 299) {
                    $response->data = [
                        'success' => true,
                        'status' => $response->statusCode,
                        'data' => $responseData,
                        '_meta' => [
                            'totalCount' => $response->headers->get('X-Pagination-Total-Count'),
                            'pageCount' => $response->headers->get('X-Pagination-Page-Count'),
                            'currentPage' => $response->headers->get('X-Pagination-Current-Page'),
                            'perPage' => $response->headers->get('X-Pagination-Per-Page'),
                        ],
                    ];
                } else {
                    $response->data = [
                        'success' => false,
                        'status' => $response->statusCode,
                        'data' => $responseData,
                    ];
                }
                return $response;
            },
        ],
    ],
    'on beforeRequest' => function ($event) {
        // Set application language based on Accept-Language header
        $request = $event->sender->getRequest();
        $acceptedLanguages = $request->getAcceptableLanguages();
        if (!empty($acceptedLanguages)) {
            Yii::$app->language = $acceptedLanguages[0];
        }
    },
    'params' => array_merge($params, [
        // Access log JSONL (1 línea JSON por request). Por defecto excluye OPTIONS.
        'accessLog' => [
            'enabled' => true,
            'file' => '@backend/runtime/access.log.jsonl',
            'excludeMethods' => ['OPTIONS'],
            // 1.0 = 100% de requests. Ajustá si querés muestreo.
            'sampleRate' => 1.0,
            // Por defecto no guardamos body para evitar PII y tamaño.
            'logBody' => false,
            // Claves a redaccionar si se habilita logBody.
            'redactKeys' => ['password', 'token', 'access_token', 'authorization'],
        ],
    ]),
];
