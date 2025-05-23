<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\widgets\admin;

use common\models\User;
use skeeks\cms\models\CmsTask;
use skeeks\crm\models\CrmProject;
use skeeks\crm\models\CrmTask;
use yii\base\Widget;

class CmsTaskViewWidget extends Widget
{
    /**
     * @var CmsTask
     */
    public $task = null;

    /**
     * @var bool Показывать только название
     */
    public $isShowOnlyName = false;

    /**
     * @var string
     */
    public $tagName = "a";

    /**
     * @var array
     */
    public $tagNameOptions = [];

    /**
     * @var int
     */
    public $prviewImageSize = 40;


    public $isShowStatus = false;


    public $isStatusShort = true;

    /**
     * @var bool
     */
    public $isAction = true;


    static protected $_isRegisterAssets = false;

    public function run()
    {
        if (self::$_isRegisterAssets === false) {
            self::$_isRegisterAssets = true;
            $this->view->registerCss(<<<CSS
.sx-task-info .img-wrapper {
    margin-right: 0.75rem;
}
.sx-task-wrapper .sx-task-status {
    margin: auto;
    margin-left: 0.5rem;
}
.sx-task-info {
    display: flex;
    align-items: center;
}
.sx-task-wrapper {
    display: flex;
}
CSS
            );
        }
        return $this->render('task-view');
    }
}