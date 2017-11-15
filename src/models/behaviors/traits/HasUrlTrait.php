<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 24.05.2015
 */

namespace skeeks\cms\models\behaviors\traits;

use skeeks\cms\models\CmsContentElementTree;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * @method string                               getAbsoluteUrl()
 * @method string                               getUrl()
 *
 * @property string absoluteUrl
 * @property string url
 */
trait HasUrlTrait
{
    /**
     * @return string
     */
    public function getAbsoluteUrl()
    {
        return $this->url;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return "";
    }
}