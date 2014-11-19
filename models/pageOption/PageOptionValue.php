<?php
/**
 * PageOptionValue
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 19.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\models\pageOption;

use skeeks\cms\base\Model;

/**
 * Class PageOption
 * @package skeeks\cms\models
 */
class PageOptionValue extends Model
{
    /**
     * @var mixed
     */
    public $value;

    public function attributeLabels()
    {
        return [
            'value' => 'Значение'
        ];
    }

    public function rules()
    {
        return [
            [['value'], 'safe'],
        ];
    }

    /**
     * @param array $data
     * @return string
     */
    public function renderForm($data = [])
    {
        $class = new \ReflectionClass($this->className());
        return \Yii::$app->getView()->renderFile(dirname($class->getFileName()) . DIRECTORY_SEPARATOR . '_form.php', array_merge(
            ['model' => $this],
            $data
        ));
    }
}