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
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;

/**
 * Class AdminCmsContentTypeController
 * @package skeeks\cms\controllers
 */
class AdminCmsContentTypeController extends AdminModelEditorController
{
    public function init()
    {
        $this->name = \Yii::t('skeeks/cms', 'Content management');
        $this->modelShowAttribute = "name";
        $this->modelClassName = CmsContentType::class;

        parent::init();
    }
}
