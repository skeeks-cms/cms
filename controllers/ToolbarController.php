<?php
/**
 * ErrorController
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 04.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\controllers;


use skeeks\cms\base\Controller;
use skeeks\cms\components\Cms;
use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\models\CmsComponentSettings;
use Yii;
use yii\web\Response;


/**
 * Class ToolbarController
 * @package skeeks\cms\controllers
 */
class ToolbarController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [];
    }

    public function actionTriggerEditMode()
    {
        if (\Yii::$app->request->isPost && \Yii::$app->request->isAjax)
        {
            \Yii::$app->response->format = Response::FORMAT_JSON;

            \Yii::$app->cmsToolbar->triggerEditMode();

            return [
                'message' => '',
                'success' => true,
            ];
        }
    }

    public function actionTriggerIsOpen()
    {
        if (\Yii::$app->request->isPost && \Yii::$app->request->isAjax)
        {
            $rr = new RequestResponse();

            if (\Yii::$app->request->post('isOpen') == "true")
            {
                $userSettings           = CmsComponentSettings::createByComponentUserId(\Yii::$app->cmsToolbar, \Yii::$app->user->id);
                $userSettings->setSettingValue('isOpen', Cms::BOOL_Y);

                if (!$userSettings->save())
                {
                    $rr->message = 'Не удалось сохранить настройки';
                    $rr->success = false;
                    return $rr;
                }

                \Yii::$app->cmsToolbar->invalidateCache();
                $rr->message = 'Сохранено';
                $rr->success = true;
            } else
            {
                $userSettings           = CmsComponentSettings::createByComponentUserId(\Yii::$app->cmsToolbar, \Yii::$app->user->id);
                $userSettings->setSettingValue('isOpen', Cms::BOOL_N);

                if (!$userSettings->save())
                {
                    $rr->message = 'Не удалось сохранить настройки';
                    $rr->success = false;
                    return $rr;
                }

                \Yii::$app->cmsToolbar->invalidateCache();
                $rr->message = 'Сохранено';
                $rr->success = true;
            }

            return $rr;
        }
    }

}
