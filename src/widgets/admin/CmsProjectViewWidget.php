<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\widgets\admin;

use common\models\User;
use skeeks\cms\models\CmsProject;
use skeeks\crm\models\CrmProject;
use yii\base\Widget;

class CmsProjectViewWidget extends Widget
{
    /**
     * @var CmsProject
     */
    public $project = null;

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

    public function run()
    {
        return $this->render('project-view');
    }
}