<?php
/**
 * HasPageOptions
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 17.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\models\behaviors;
use skeeks\cms\base\behaviors\ActiveRecord;

/**
 * Class HasPageOptions
 * @package skeeks\cms\models\behaviors
 */
class HasPageOptions extends ActiveRecord
{
    /**
     * @var string
     */
    public $fieldName = 'page_options';

    /**
     * Разрешенные опции
     * @var array
     */
    public $enabledOptions  = [];

    /**
     * Запрещенные опции
     * @var array
     */
    public $disabledOptions = [];

    /**
     * @param \skeeks\cms\base\db\ActiveRecord $owner
     * @throws \skeeks\cms\Exception
     */
    public function attach($owner)
    {
        $owner->attachBehavior("serialize", [
            "class"  => Serialize::className(),
            "fields" => [$this->fieldName]
        ]);

        parent::attach($owner);
    }
}