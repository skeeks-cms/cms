<?php
/**
 * CmsController
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 12.11.2014
 * @since 1.0.0
 */

namespace skeeks\cms\controllers;

use skeeks\cms\App;
use skeeks\cms\base\Controller;

/**
 * Class CmsController
 * @package skeeks\cms\controllers
 */
class CmsController extends Controller
{
    public function actionIndex()
    {
        return $this->output(\Yii::$app->cms->moduleCms()->getDescriptor());
    }

    public function actionVersion()
    {
        return $this->output(\Yii::$app->cms->moduleCms()->getDescriptor());
    }

    public function actionInstall()
    {
        $this->layout = '@skeeks/cms/modules/admin/views/layouts/unauthorized.php';

        return $this->render('install', [
        ]);
    }
}