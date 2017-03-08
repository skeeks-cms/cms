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
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;

/**
 * Class AdminCmsContentTypeController
 * @package skeeks\cms\controllers
 */
class AdminCmsContentController extends AdminModelEditorController
{
    public function init()
    {
        $this->name                   = "Управление контентом";
        $this->modelShowAttribute      = "name";
        $this->modelClassName          = CmsContent::className();

        parent::init();
    }

    /**
     * @return string
     */
    public function getIndexUrl()
    {
        $contentTypePk = null;

        if ($this->model)
        {
            if ($contentType = $this->model->contentType)
            {
                $contentTypePk = $contentType->id;
            }
        }

        return UrlHelper::construct(["cms/admin-cms-content-type/update", 'pk' => $contentTypePk])->enableAdmin()->toString();
    }
}
