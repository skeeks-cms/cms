<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 30.05.2015
 */
namespace skeeks\cms\modules\admin\controllers;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\Search;
use skeeks\cms\modules\admin\actions\AdminAction;
use skeeks\cms\modules\admin\controllers\helpers\rules\NoModel;
use skeeks\sx\Dir;
use skeeks\sx\File;
use yii\data\ArrayDataProvider;
use yii\filters\VerbFilter;
use yii\grid\GridView;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;

use Yii;

/**
 * Class InfoController
 * @package skeeks\cms\modules\admin\controllers
 */
class InfoController extends AdminController
{
    public function init()
    {
        $this->name = "Информация о системе";

        parent::init();
    }

    public function actions()
    {
        return
        [
            'index' =>
            [
                'class'         => AdminAction::className(),
                'name'          => 'Общая информация',
                'viewParams'    => $this->indexData(),
            ]
        ];
    }

    public function indexData()
    {
        return
        [
            'phpVersion' => PHP_VERSION,
            'yiiVersion' => \Yii::getVersion(),
            'application' => [
                'yii' => \Yii::getVersion(),
                'name' => \Yii::$app->cms->appName,
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
        ];
    }


    public function actionPhp()
    {
         phpinfo();
         die;
    }

    /**
     * Перегенерация файла модулей.
     * @return \yii\web\Response
     */
    public function actionUpdateModulesFile()
    {
        if (\Yii::$app->cms->generateModulesConfigFile())
        {
            \Yii::$app->session->setFlash('success', 'Файл автоматических путей к модулям успешно обновлен');
        } else
        {
            \Yii::$app->session->setFlash('error', 'Файл автоматических путей к модулям не обновлен');
        }

        return $this->redirect(\Yii::$app->request->getReferrer());
    }

    /**
     * Перегенерация файла модулей.
     * @return \yii\web\Response
     */
    public function actionWriteEnvGlobalFile()
    {
        $env = (string) \Yii::$app->request->get('env');
        if (!$env)
        {
            \Yii::$app->session->setFlash('error', 'Не указано окружение для записи');
            return $this->redirect(\Yii::$app->request->getReferrer());
        }

        $content =<<<PHP
<?php
defined('YII_ENV') or define('YII_ENV', '{$env}');
PHP;

        $file = new File(APP_ENV_GLOBAL_FILE);
        if ($file->write($content))
        {
            \Yii::$app->session->setFlash('success', 'Файл успешно создан и записан');
        } else
        {
            \Yii::$app->session->setFlash('error', 'Не удалось записать файл');
        }

        return $this->redirect(\Yii::$app->request->getReferrer());
    }

    public function actionRemoveEnvGlobalFile()
    {
        $file = new File(APP_ENV_GLOBAL_FILE);
        if ($file->remove())
        {
            \Yii::$app->session->setFlash('success', 'Файл успешно удален');
        } else
        {
            \Yii::$app->session->setFlash('error', 'Не удалось удалить файл');
        }

        return $this->redirect(\Yii::$app->request->getReferrer());
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