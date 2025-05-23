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

class CmsWorkerViewWidget extends Widget
{
    /**
     * @var CmsUser
     */
    public $user = null;

    /**
     * @var bool Показывать только название
     */
    public $isShowOnlyName = false;

    /**
     * @var string
     */
    public $tagName = "a";


    public $viewFile = "worker-view";

    /**
     * @var string
     */
    public $append = "";

    /**
     * @var array
     */
    public $tagNameOptions = [];

    /**
     * @var int
     */
    public $prviewImageSize = 50;
    
    public $isSmall = false;

    public function run()
    {
        if ($this->user) {
            $cache = \Yii::$app->cache->get("cmsUser{$this->user->id}" . $this->tagName . $this->prviewImageSize . $this->isSmall);
            if ($cache) {
                return $cache;
            }

            $result = $this->render($this->viewFile);
            \Yii::$app->cache->set("cmsUser{$this->user->id}", $result, 2);
            return $result;
        } else {
            return "";
        }

    }
}