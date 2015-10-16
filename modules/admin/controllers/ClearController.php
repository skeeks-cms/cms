<?php
/**
 * ClearController
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 08.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\modules\admin\controllers;
use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\modules\admin\actions\AdminAction;
use skeeks\cms\modules\admin\controllers\helpers\rules\NoModel;
use skeeks\sx\Dir;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * Class IndexController
 * @package skeeks\cms\modules\admin\controllers
 */
class ClearController extends AdminController
{
    public function init()
    {
        $this->name = \Yii::t('app',"Deleting temporary files");
        parent::init();
    }

    public function actions()
    {
        return
        [
            "index" =>
            [
                "class"        => AdminAction::className(),
                "name"         => \Yii::t('app',"Clearing temporary data"),
                "callback"     => [$this, 'actionIndex'],
            ],
        ];
    }

    public function actionIndex()
    {
        $clearDirs =
        [
            [
                'label'     => 'common ' . \Yii::t('app','temporary files'),
                'dir'       => new Dir(\Yii::getAlias('@common/runtime'), false)
            ],

            [
                'label'     => 'console ' . \Yii::t('app','temporary files'),
                'dir'       => new Dir(\Yii::getAlias('@console/runtime'), false)
            ],


            [
                'label'     => 'runtime (' . \Yii::t('app','current site') . ')',
                'dir'       => new Dir(\Yii::getAlias('@runtime'), false)
            ],

            [
                'label'     => \Yii::t('app','Cache files').' (' . \Yii::t('app','current site') . ')',
                'dir'       => new Dir(\Yii::getAlias('@runtime/cache'), false)
            ],

            [
                'label'     => \Yii::t('app','Files debug information') . ' (' . \Yii::t('app','current site') . ')',
                'dir'       => new Dir(\Yii::getAlias('@runtime/debug'), false)
            ],

            [
                'label'     => \Yii::t('app','Log files') . ' (' . \Yii::t('app','current site') . ')',
                'dir'       => new Dir(\Yii::getAlias('@runtime/logs'), false)
            ],

            /*[
                'label'     => 'Временные js и css файлы (текущий сайт)',
                'dir'       => new Dir(\Yii::getAlias('@app/web/assets'), false)
            ]*/

        ];

        $rr = new RequestResponse();
        if ($rr->isRequestAjaxPost())
        {
            foreach ($clearDirs as $data)
            {
                $dir = ArrayHelper::getValue($data, 'dir');
                if ($dir instanceof Dir)
                {
                    if ($dir->isExist())
                    {
                        $dir->clear();
                    }
                }
            }

            \Yii::$app->db->getSchema()->refresh();
            \Yii::$app->cache->flush();
            \Yii::$app->cms->generateModulesConfigFile();

            $rr->success = true;
            $rr->message = \Yii::t('app','Cache cleared');
            return $rr;
        }

        return $this->render('index', [
            'clearDirs'     => $clearDirs,
        ]);
    }


}