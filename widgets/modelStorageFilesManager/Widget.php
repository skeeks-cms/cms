<?php
/**
 * Widget
 * Файловый менеджер для сущьности
 *
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 22.10.2014
 * @since 1.0.0
 */
namespace skeeks\cms\widgets\modelStorageFilesManager;

use skeeks\cms\base\db\ActiveRecord;
use Yii;
use \skeeks\cms\base\Widget as CmsWidget;
use yii\base\InvalidConfigException;

/**
 * Class Widget
 * @package skeeks\cms\widgets
 */
class Widget extends CmsWidget
{
    public $model = null;

    public function init()
    {
        parent::init();

        if (!$this->model instanceof ActiveRecord)
        {
            throw new InvalidConfigException("Некорректно сконфигурирован виджет: " . Widget::className() . ". Укажите 'model' instanceof " . ActiveRecord::className());
        }
    }

    /**
     * @inheritdoc
     */
    public function run()
    {

    }



}
