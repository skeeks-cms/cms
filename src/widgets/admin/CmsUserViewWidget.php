<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\widgets\admin;

use skeeks\cms\backend\assets\BackendAsset;
use skeeks\cms\models\CmsUser;
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
        BackendAsset::register($this->view);

        if (!$this->cmsUser) {
            return "";
        }

        return $this->render($this->viewFile);
    }
}
