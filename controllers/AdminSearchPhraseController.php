<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 15.05.2015
 */
namespace skeeks\cms\controllers;

use skeeks\cms\components\Cms;
use skeeks\cms\grid\SiteColumn;
use skeeks\cms\models\CmsAgent;
use skeeks\cms\models\CmsContent;
use skeeks\cms\models\CmsSearchPhrase;
use skeeks\cms\modules\admin\actions\modelEditor\AdminMultiModelEditAction;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;
use skeeks\cms\modules\admin\traits\AdminModelEditorStandartControllerTrait;
use yii\helpers\ArrayHelper;

/**
 * Class AdminSearchPhraseController
 * @package skeeks\cms\controllers
 */
class AdminSearchPhraseController extends AdminModelEditorController
{
    use AdminModelEditorStandartControllerTrait;

    public function init()
    {
        $this->name                     = "Список переходов";
        $this->modelShowAttribute       = "phrase";
        $this->modelClassName           = CmsSearchPhrase::className();

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return ArrayHelper::merge(parent::actions(),
            [
                'create' =>
                [
                    'visible' => false
                ],

                'index' =>
                [
                    "columns"      => [
                        'phrase',

                        [
                            'class'         => \skeeks\cms\grid\DateTimeColumnData::className(),
                            'attribute'     => "created_at"
                        ],

                        [
                            'attribute'     => "result_count"
                        ],

                        [
                            'attribute'     => "pages"
                        ],

                        [
                            'class'         => SiteColumn::className()
                        ],

                    ],
                ],

            ]
        );
    }

}
