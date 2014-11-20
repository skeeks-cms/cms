<?php
/**
 * ModelActionViews
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 20.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\components;

use skeeks\cms\base\db\ActiveRecord;
use skeeks\cms\models\ComponentModel;
use skeeks\cms\models\StorageFile;
use Yii;
use yii\base\Component;
use yii\helpers\ArrayHelper;
use yii\web\UploadedFile;

/**
 * @method ComponentModel[]   getComponents()
 * @method ComponentModel     getComponent($id)
 *
 * Class CollectionComponents
 * @package skeeks\cms\components
 */
class ModelActionViews extends CollectionComponents
{
    public $componentClassName  = 'skeeks\cms\models\ComponentModel';
}