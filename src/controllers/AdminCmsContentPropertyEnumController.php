<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 17.05.2015
 */

namespace skeeks\cms\controllers;

use skeeks\cms\backend\actions\BackendModelAction;
use skeeks\cms\backend\BackendAction;
use skeeks\cms\models\CmsContentPropertyEnum;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * Class AdminCmsContentPropertyEnumController
 * @package skeeks\cms\controllers
 */
class AdminCmsContentPropertyEnumController extends AdminModelEditorController
{
    public function init()
    {
        $this->name = \Yii::t('skeeks/cms', 'Managing property values');
        $this->modelShowAttribute = "value";
        $this->modelClassName = CmsContentPropertyEnum::class;

        parent::init();
    }

    /**
     * @inheritdoc
     */
    /*public function actions()
    {
        return ArrayHelper::merge(parent::actions(),
        [
            'bind' =>
            [
                'class' => BackendAction::class,
                'name' => 'Привязать'
            ]
        ]);
    }*/
}
