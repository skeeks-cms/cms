<?php
/**
 * AdminFileManagerController
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 06.02.2015
 * @since 1.0.0
 */
namespace skeeks\cms\controllers;

use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\Comment;
use skeeks\cms\modules\admin\actions\AdminAction;
use skeeks\cms\modules\admin\controllers\AdminController;
use Yii;
use skeeks\cms\models\User;
use skeeks\cms\models\searchs\User as UserSearch;

/**
 * Class AdminMarketplaceController
 * @package skeeks\cms\controllers
 */
class AdminMarketplaceController extends AdminController
{
    public function init()
    {
        $this->name                   = "Маркетплейс";
        parent::init();
    }

    public function actions()
    {
        return
        [
            "index" =>
            [
                "class"        => AdminAction::className(),
                "name"         => "Установленные расширения",
            ],

            "catalog" =>
            [
                "class"        => AdminAction::className(),
                "name"         => "Каталог решений",
            ],

            "update" =>
            [
                "class"        => AdminAction::className(),
                "name"         => "Обновление платформы",
            ],
        ];
    }
}
