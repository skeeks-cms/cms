<?php
/**
 * Publication
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 31.10.2014
 * @since 1.0.0
 */

namespace skeeks\cms\models;

use skeeks\cms\base\models\PageAdvanced;
use Yii;

/**
 * Class Publication
 * @package skeeks\cms\models
 */
class Publication extends PageAdvanced
{
    protected $_viewPage = "cms/publication/view";
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%publication}}';
    }

}
