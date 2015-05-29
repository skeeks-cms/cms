<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 15.05.2015
 */
namespace skeeks\cms\controllers;

use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\CmsContent;
use skeeks\cms\models\CmsContentElement;
use skeeks\cms\models\CmsContentType;
use skeeks\cms\modules\admin\controllers\AdminController;
use skeeks\cms\modules\admin\controllers\AdminModelEditorSmartController;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;
use Yii;
use skeeks\cms\models\User;
use skeeks\cms\models\searchs\User as UserSearch;
use yii\base\ActionEvent;

/**
 * Class AdminCmsContentTypeController
 * @package skeeks\cms\controllers
 */
class AdminCmsContentElementController extends AdminModelEditorSmartController
{
    public function init()
    {
        $this->_label                   = "Элементы";
        $this->_modelShowAttribute      = "name";
        $this->_modelClassName          = CmsContentElement::className();

        $this->modelValidate = true;
        $this->enableScenarios = true;

        parent::init();
    }

    public $content;

    /**
     * @param ActionEvent $e
     */
    protected function _beforeAction(ActionEvent $e)
    {
        if ($content_id = \Yii::$app->request->get('content_id'))
        {
            $this->content = CmsContent::findOne($content_id);
        }

        if ($this->content)
        {
            if ($this->content->name_meny)
            {
                $this->_label = $this->content->name_meny;
            }

        }

        parent::_beforeAction($e);
    }

    /**
     * @return string
     */
    public function getIndexUrl()
    {
        return UrlHelper::construct($this->id . '/' . $this->action->id, [
            'content_id' => \Yii::$app->request->get('content_id')
        ])->enableAdmin()->setRoute('index')->normalizeCurrentRoute()->toString();
    }

}
