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

class CmsUserViewWidget extends Widget
{
    /**
     * @var CmsUser
     */
    public $cmsUser = null;

    /**
     * @var bool Показывать только название
     */
    public $isShowOnlyName = false;

    /**
     * @var string
     */
    public $tagName = "a";


    public $viewFile = "user-view";

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
        if ($this->cmsUser) {
            $cache = \Yii::$app->cache->get("cmsUser{$this->cmsUser->id}" . $this->tagName . $this->prviewImageSize);
            if ($cache) {
                return $cache;
            }

            $result = $this->render($this->viewFile);
            \Yii::$app->cache->set("cmsUser{$this->cmsUser->id}", $result, 2);
            return $result;
        } else {
            return "";
        }

    }
}