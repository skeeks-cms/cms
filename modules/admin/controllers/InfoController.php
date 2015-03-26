<?php
/**
 * InfoController
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 30.01.2015
 * @since 1.0.0
 */

namespace skeeks\cms\modules\admin\controllers;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\Search;
use skeeks\cms\modules\admin\controllers\helpers\rules\NoModel;
use skeeks\sx\Dir;
use yii\data\ArrayDataProvider;
use yii\filters\VerbFilter;
use yii\grid\GridView;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;

use Yii;
/**
 * Class IndexController
 * @package skeeks\cms\modules\admin\controllers
 */
class InfoController extends AdminController
{
    public function init()
    {
        $this->_label = "Информация о системе";

        parent::init();
    }


    /**
     * @return array
     */
    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [

            self::BEHAVIOR_ACTION_MANAGER =>
            [
                /*"actions" =>
                [
                    "index" =>
                    [
                        "label"         => "Работа с базой данных",
                        "rules"         => NoModel::className()
                    ],
                ]*/
            ]
        ]);
    }

    public function actionPhp()
    {
         phpinfo();
         die;
    }
    public function actionIndex()
    {
        return $this->render('index', [

            'phpVersion' => PHP_VERSION,
            'yiiVersion' => \Yii::getVersion(),
            'application' => [
                'yii' => \Yii::getVersion(),
                'name' => \Yii::$app->name,
                'env' => YII_ENV,
                'debug' => YII_DEBUG,
            ],
            'php' => [
                'version' => PHP_VERSION,
                'xdebug' => extension_loaded('xdebug'),
                'apc' => extension_loaded('apc'),
                'memcache' => extension_loaded('memcache'),
                'xcache' => extension_loaded('xcache'),
                'imagick' => extension_loaded('imagick'),
                'gd' => extension_loaded('gd'),
            ],
            'extensions' => $this->getExtensions(),

        ]);
    }

    /**
     * Returns data about extensions
     *
     * @return array
     */
    public function getExtensions()
    {
        $data = [];
        foreach (\Yii::$app->extensions as $extension) {
            $data[$extension['name']] = $extension['version'];
        }

        return $data;
    }



}