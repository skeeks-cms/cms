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
use skeeks\cms\helpers\UrlHelper;
use yii\db\Exception;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

/**
 * Class CmsController
 * @package skeeks\cms\controllers
 */
class CmsController extends Controller
{
    public function actionIndex()
    {
        return $this->output("Система управления сайтом: " . Html::a("Yii2 cms — " . \Yii::$app->cms->descriptor, \Yii::$app->cms->descriptor->homepage));
    }

    public function actionVersion()
    {
        return $this->output(\Yii::$app->cms->moduleCms()->getDescriptor());
    }

    public function actionInstall()
    {
        $message    = 'Проект успешно установлен<br />' .
            Html::a('Перейти на главную страницу', Url::home()) . " | " . Html::a('Перейти к управлению сайтом', UrlHelper::construct('/admin/index')->enableAdmin()->toString());

        \Yii::$app->cmsToolbar->enabled = false;
        $this->layout = '@skeeks/cms/modules/admin/views/layouts/unauthorized.php';

        $connectToDbForm = new \skeeks\cms\models\forms\ConnectToDbForm();

        $rr = new RequestResponse();

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

        //Если в базе нет таблиц
        if (!\Yii::$app->db->schema->getTableSchemas('', true))
        {
            $jsOptions = [
                'backendDbRestore' => Url::to(['/cms/cms/install-db-restore'])
            ];

            return $this->render('db-is-empty', [
                'jsOptions' => Json::encode($jsOptions)
            ]);
        }

        return $this->render('install', [
            'message' => $message
        ]);
    }

    /**
     * @return RequestResponse
     */
    public function actionInstallDbRestore()
    {
        $rr = new RequestResponse();

        if ($rr->isRequestAjaxPost())
        {
            //Если в базе нет таблиц
            if (!\Yii::$app->db->schema->getTableSchemas('', true))
            {
                ignore_user_abort(true);
                set_time_limit(0);

                $name = \Yii::$app->request->post('name');

                if ($name)
                {
                    \Yii::$app->dbDump->dumpRestore($name);
                } else
                {
                    \Yii::$app->dbDump->dumpNewInstall();
                }

                \Yii::$app->db->schema->refresh();

                $rr->success = true;
                $rr->message = "Успешно установлено";
            }
        }

        return $rr;
    }

}