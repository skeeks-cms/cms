<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\widgets\admin;

use common\models\User;
use skeeks\cms\backend\assets\BackendAsset;
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


    public function run()
    {
        BackendAsset::register($this->view);

        return $this->render('task-view');
    }
}
