<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\widgets\admin;

use common\models\User;
use skeeks\cms\models\CmsUser;
use skeeks\cms\widgets\assets\CmsActivityAsset;
use yii\base\Widget;

class CmsCommentWidget extends Widget
{
    /**
     * @var null 
     */
    public $model = null;

    /**
     * @var bool Перезагружать контейнер pjax если это возможно, если его нет, то перезагрузить страницу после написания комментария
     */
    public $isPjax = true;

    /**
     * @var string|null
     */
    public $pinnedLabel = null;

    /**
     * @var bool Показывать загрузку файлов.
     */
    public $isShowAttachments = true;

    /**
     * @var bool Показывать управление закреплением комментария.
     */
    public $isShowPin = true;
    
    public $backend_url = ['/cms/admin-cms-log/add-comment'];

    public function run()
    {
        CmsActivityAsset::register($this->getView());

        return $this->render('comment');
    }
}
