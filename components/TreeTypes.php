<?php
/**
 * TreeTypes
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 11.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\components;

use skeeks\cms\base\db\ActiveRecord;
use skeeks\cms\models\StorageFile;
use skeeks\cms\models\TreeType;
use Yii;
use yii\base\Component;
use yii\helpers\ArrayHelper;
use yii\web\UploadedFile;

/**
 * @method TreeType[] getComponents()
 * Class CollectionComponents
 * @package skeeks\cms\components
 */
class TreeTypes extends CollectionComponents
{
    public $componentClassName  = 'skeeks\cms\models\TreeType';
}