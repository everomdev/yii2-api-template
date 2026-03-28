<?php

namespace backend\controllers;


use backend\helpers\ListActionDataProviderHelper;
use common\models\User;
use Yii;
use yii\filters\auth\CompositeAuth;
use yii\filters\Cors;
use yii\rest\ActiveController;

class BaseActiveController extends ActiveController
{
    public function actions()
    {
        $actions = parent::actions();

        $searchModel = isset($this->searchModelClass) ? $this->searchModelClass : "{$this->modelClass}Search";
        $actions ['index']['prepareDataProvider'] = function ($action) use ($searchModel) {
            $a = \Yii::createObject([
                'class' => ListActionDataProviderHelper::class,
                'modelClass' => $this->modelClass,
                'dataFilter' => [
                    'class' => 'yii\data\ActiveDataFilter',
                    'searchModel' => $searchModel,
                ],
                'prepareQuery' => function ($action) {
                    return $this->modelClass::find();
                },
            ]);

            return $a->getDataProvider();
        };

        return $actions;
    }

    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $behaviors['corsFilter'] = [
            'class' => Cors::className(),
            'cors' => [
                'Origin' => ['*'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
            ],
        ];

        $behaviors['authenticator'] = [
            'class' => CompositeAuth::className(),
            'authMethods' => [
                \yii\filters\auth\HttpBearerAuth::className(),
            ],

        ];

        $behaviors['authenticator']['except'] = [
            'options',
        ];

        return $behaviors;
    }

//    public function beforeAction($action)
//    {
//        if (!parent::beforeAction($action)) {
//            return false;
//        }
//
//        // Evitar bloquear preflight CORS/OPTIONS
//        if ($action->id === 'options') {
//            return true;
//        }
//
//        return true;
//    }

}
