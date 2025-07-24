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

class CmsLogListWidget extends Widget
{
    public $query = null;

    public $is_show_model = true;
    
    public $list_view_config = [];

    public function run()
    {
        return $this->render('log-list');
    }
}