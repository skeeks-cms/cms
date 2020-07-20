<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\controllers;

use skeeks\cms\assets\CmsAsset;
use skeeks\cms\seo\models\CmsContentElement;
use skeeks\cms\seo\models\CmsSearchPhrase;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class FaviconController extends Controller
{
    /**
     * @return string
     */
    public function actionOnRequest($extension)
    {
        $extension = strtolower($extension);
        if (!in_array($extension, ["png", "jpeg", "gif", "bmp", "ico"])) {
            throw new NotFoundHttpException("favicon." . $extension . " is not found!");
        }

        $data = pathinfo(\Yii::$app->skeeks->site->faviconUrl);
        $siteExtension = strtolower(ArrayHelper::getValue($data, "extension"));

        //Если на сайт загружена фавион с тем же ресширением показываем, иначе нет.
        if ($siteExtension == $extension) {
            //вывести фавикон

            \Yii::$app->response->format = \yii\web\Response::FORMAT_RAW;
            \Yii::$app->response->headers->add('content-type', \Yii::$app->skeeks->site->faviconType);
            \Yii::$app->response->data = file_get_contents(\Yii::$app->skeeks->site->faviconRootSrc);
            return \Yii::$app->response;

        } else {
            //Если на сайт загружена фавиконка не ico, то покажем стандартную .ico skeeks
            if ($extension == 'ico') {
                \Yii::$app->response->format = \yii\web\Response::FORMAT_RAW;
                \Yii::$app->response->headers->add('content-type','image/x-icon');
                \Yii::$app->response->data = file_get_contents(\Yii::getAlias('@skeeks/cms/assets/src/favicon.ico'));
                return \Yii::$app->response;
            }
        }

        throw new NotFoundHttpException("favicon." . $extension . " is not found!");
    }
}
