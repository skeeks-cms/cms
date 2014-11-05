<?php
/**
 * UserGroup
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 06.11.2014
 * @since 1.0.0
 */

namespace skeeks\cms\models;

use skeeks\cms\helpers\UrlHelper;
use Yii;

/**
 * Class Publication
 * @package skeeks\cms\models
 */
class UserGroup extends Core
{
    public $viewPageTemplate = "cms/publication/view";

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user_group}}';
    }

}
