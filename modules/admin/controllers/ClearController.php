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
use skeeks\cms\helpers\UrlHelper;
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
        $this->_label = "Удаление временных файлов";

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
                "actions" =>
                [
                    "index" =>
                    [
                        "label"         => "Чистка всего",
                        "rules"         => NoModel::className()
                    ],
                ]
            ]
        ]);
    }

    public function actionIndex()
    {
        $clearDirs =
        [
            [
                'label'     => 'Временные закрытые файлы',
                'dir'       => new Dir(\Yii::getAlias('@runtime'), false)
            ],

            [
                'label'     => 'Файлы кэша',
                'dir'       => new Dir(\Yii::getAlias('@runtime/cache'), false)
            ],

            [
                'label'     => 'Файлы дебаг информации',
                'dir'       => new Dir(\Yii::getAlias('@runtime/debug'), false)
            ],

            [
                'label'     => 'Файлы логов',
                'dir'       => new Dir(\Yii::getAlias('@runtime/logs'), false)
            ],

            [
                'label'     => 'Временные js и css файлы',
                'dir'       => new Dir(\Yii::getAlias('@app/web/assets'), false)
            ]

        ];

        if (\Yii::$app->request->isPost)
        {
            foreach ($clearDirs as $data)
            {

                $dir = ArrayHelper::getValue($data, 'dir');
                if ($dir instanceof Dir)
                {
                    if ($dir->isExist())
                    {
                        $dir->clear();
                        \Yii::$app->getSession()->setFlash('success', 'Кэш успешно очищен');
                    }
                }
            }
        }

        return $this->render('index', [
            'clearDirs'     => $clearDirs,
        ]);
    }


}