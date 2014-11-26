<?php
/**
 * ModelFilesGroup
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 26.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\models\helpers;
use skeeks\cms\models\ComponentModel;
use yii\db\ActiveRecord;

/**
 * Class ModelFilesGroup
 * @package skeeks\cms\models\helpers
 */
class ModelFilesGroup extends ComponentModel
{
    /**
     * @var array
     */
    public $config = [];
    /**
     * @var array
     */
    public $items = [];

    /**
     * @var ActiveRecord
     */
    public $owner = null;
}