<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 13.08.2015
 */

namespace skeeks\cms\helpers;

use skeeks\cms\models\CmsContentElement;
use yii\base\Component;
use yii\helpers\ArrayHelper;


/**
 * Class CmsContentElementHelper
 * @package skeeks\cms\helpers
 */
abstract class CmsContentElementHelper extends Component
{
    /**
     * @var array
     */
    static public $instances = [];

    /**
     * @var CmsContentElement
     */
    public $model;

    /**
     * @param CmsContentElement $model
     * @param $data
     */
    public function __construct($model, $data = [])
    {
        $data['model'] = $model;
        static::$instances[$model->id] = $this;

        parent::__construct($data);


    }

    /**
     * @param CmsContentElement $model
     * @param array $data
     * @return static
     */
    public static function instance($model, $data = [])
    {
        if ($package = ArrayHelper::getValue(static::$instances, $model->id)) {
            return $package;
        }

        return new static($model, $data);
    }
}