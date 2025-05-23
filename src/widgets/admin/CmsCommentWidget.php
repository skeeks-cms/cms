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
use skeeks\crm\models\CrmContractor;
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

    public function run()
    {
        return $this->render('comment');
    }
}