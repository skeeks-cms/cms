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

class CmsLogListWidget extends Widget
{
    public $query = null;

    public $is_show_model = true;

    /**
     * The record of the card the feed belongs to: with is_show_model = false
     * only its link is hidden, the records of other models (its deals) keep
     * theirs — «Сделка №927 Ромашка».
     *
     * @var \yii\db\ActiveRecord|null
     */
    public $ownModel = null;

    public $is_show_pin_controls = false;

    public $is_raised = true;
    
    public $list_view_config = [];

    public function run()
    {
        CmsActivityAsset::register($this->getView());

        return $this->render('log-list');
    }
}
