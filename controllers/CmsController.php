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
use skeeks\cms\exceptions\NotConnectedToDbException;
use skeeks\cms\helpers\RequestResponse;
use yii\db\Exception;

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
        \Yii::$app->cmsToolbar->enabled = false;
        $this->layout = '@skeeks/cms/modules/admin/views/layouts/unauthorized.php';

        $connectToDbForm = new \skeeks\cms\models\forms\ConnectToDbForm();

        $rr = new RequestResponse();

        /*if ($rr->isRequestOnValidateAjaxForm())
        {
            return $rr->ajaxValidateForm($connectToDbForm);
        }*/

        if ($rr->isRequestAjaxPost())
        {
            if ($connectToDbForm->load(\Yii::$app->request->post()))
            {
                if ($connectToDbForm->hasConnect())
                {
                    if ($connectToDbForm->write())
                    {
                        $rr->success = true;
                        $rr->message = "Файл с настройками создан";
                    } else
                    {
                        $rr->success = false;
                        $rr->message = "Не удалось записать настройки в файл";
                    }

                } else
                {
                    $rr->success = false;
                    $rr->message = "Не удалось подключиться к базе данных";
                }
            } else
            {
                $rr->success = false;
                $rr->message = "Некорректные настройки подключения к базе";
            }

            return $rr;
        }

        try
        {
            \Yii::$app->db->open();
        } catch (Exception $e)
        {
            if (in_array($e->getCode(), NotConnectedToDbException::$invalidConnectionCodes))
            {
                return $this->render('not-connected-to-db', [
                    'connectToDbForm' => $connectToDbForm
                ]);
            }
        }

        return $this->render('install', [
            'message' => $message
        ]);
    }


}