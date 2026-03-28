<?php

namespace backend\helpers;

use Yii;
use yii\data\ActiveDataProvider;
use yii\data\DataFilter;

/**
 * ListActionDataProviderHelper implements a helper for building data lists DataProviders.
 *
 * Example:
 *
 *
 * $a = \Yii::createObject([
 *     'class' => 'api\helpers\ListActionDataProviderHelper',
 *     'modelClass' => $this->modelClass,
 *     'dataFilter' => [
 *         'class' => 'yii\data\ActiveDataFilter',
 *         'searchModel' => 'common\models\TrainingSearch',
 *     ],
 *     'prepareQuery' => function($action){
 *         return Training::find()->andWhere(['=', 'box_id', $action->params[0]]);
 *     },
 * ]);
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class ListActionDataProviderHelper
{
    /**
     * @var callable a PHP callable that will be called to prepare a data provider that
     * should return a collection of the models. If not set, [[prepareDataProvider()]] will be used instead.
     * The signature of the callable should be:
     *
     * ```php
     * function (IndexAction $action) {
     *     // $action is the action object currently running
     * }
     * ```
     *
     * The callable should return an instance of [[ActiveDataProvider]].
     *
     * If [[dataFilter]] is set the result of [[DataFilter::build()]] will be passed to the callable as a second parameter.
     * In this case the signature of the callable should be the following:
     *
     * ```php
     * function (IndexAction $action, mixed $filter) {
     *     // $action is the action object currently running
     *     // $filter the built filter condition
     * }
     * ```
     */
    public $prepareDataProvider;
    /**
     * @var DataFilter|null data filter to be used for the search filter composition.
     * You must setup this field explicitly in order to enable filter processing.
     * For example:
     *
     * ```php
     * [
     *     'class' => 'yii\data\ActiveDataFilter',
     *     'searchModel' => function () {
     *         return (new \yii\base\DynamicModel(['id' => null, 'name' => null, 'price' => null]))
     *             ->addRule('id', 'integer')
     *             ->addRule('name', 'trim')
     *             ->addRule('name', 'string')
     *             ->addRule('price', 'number');
     *     },
     * ]
     * ```
     *
     * @see DataFilter
     *
     * @since 2.0.13
     */
    public $dataFilter;

    public $modelClass;

    /*
     * @var callable a PHP callable that will be called to prepare a query that will be used for
     * retrieving data
     */
    public $prepareQuery;

    public $params;


    /**
     * @return ActiveDataProvider
     */
    public function getDataProvider(...$params)
    {
        $this->params = $params;

        return $this->prepareDataProvider();
    }

    /**
     * Prepares the data provider that should return the requested collection of the models.
     * @return ActiveDataProvider
     */
    protected function prepareDataProvider()
    {
        $requestParams = Yii::$app->getRequest()->getBodyParams();
        if (empty($requestParams)) {
            $requestParams = Yii::$app->getRequest()->getQueryParams();
        }

        $filter = null;
        if ($this->dataFilter !== null) {
            $this->dataFilter = Yii::createObject($this->dataFilter);
            if ($this->dataFilter->load($requestParams)) {
                $filter = $this->dataFilter->build();
                if ($filter === false) {
                    return $this->dataFilter;
                }
            }
        }

        if ($this->prepareDataProvider !== null) {
            return call_user_func($this->prepareDataProvider, $this, $filter);
        }

        /* @var $modelClass \yii\db\BaseActiveRecord */
        $modelClass = $this->modelClass;

        $query = $this->prepareQuery($modelClass, $filter);

        return Yii::createObject([
            'class' => ActiveDataProvider::className(),
            'query' => $query,
            'pagination' => false,
//            'pagination' => [
//                'params' => $requestParams,
//            ],
            'sort' => [
                'params' => $requestParams,
            ],
        ]);
    }

    public function prepareQuery($modelClass, $filter)
    {
        if ($this->prepareQuery !== null) {
            $query = call_user_func($this->prepareQuery, $this);
        } else {
            $query = $modelClass::find();
        }
        if (!empty($filter)) {
            $query->andWhere($filter);
        }
        return $query;
    }
}
