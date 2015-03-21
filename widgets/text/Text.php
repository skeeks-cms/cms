<?php
/**
 * Text
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 09.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\widgets\text;

use skeeks\cms\base\Widget;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * Class Text
 * @package skeeks\cms\widgets\text
 */
class Text extends Widget
{
    static public function getDescriptorConfig()
    {
        return ArrayHelper::merge(parent::getDescriptorConfig(), [
            'name' => 'Статичный блок'
        ]);
    }

    /**
     * @var null|string
     */
    public $text = null;

    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['text'], 'string'],
        ]);
    }

    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'text'                         => 'Произвольный текст',
        ]);
    }

    /**
     * @return string
     */
    public function run()
    {
        return (string) $this->text;
    }
}
