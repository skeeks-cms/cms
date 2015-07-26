<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 15.05.2015
 */
namespace skeeks\cms\controllers;

use skeeks\cms\models\CmsContentType;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * Class AdminCmsContentTypeController
 * @package skeeks\cms\controllers
 */
class AdminCmsContentTypeController extends AdminModelEditorController
{
    public function init()
    {
        $this->name                   = "Управление контентом";
        $this->modelShowAttribute      = "name";
        $this->modelClassName          = CmsContentType::className();

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return ArrayHelper::merge(parent::actions(),
            [
                'index' =>
                [
                    "columns" => [
                        'name',
                        'code',

                        [
                            'value'     => function(\skeeks\cms\models\CmsContentType $model)
                            {
                                $contents = \yii\helpers\ArrayHelper::map($model->cmsContents, 'id', 'name');
                                return implode(', ', $contents);
                            },

                            'label' => 'Контент',
                        ],

                        'priority',
                    ],
                ],
            ]
        );
    }

}
