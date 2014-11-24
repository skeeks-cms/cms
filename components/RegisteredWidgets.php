<?php
/**
 * RegisteredWidgets
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 09.11.2014
 * @since 1.0.0
 */

namespace skeeks\cms\components;

use skeeks\cms\base\db\ActiveRecord;
use skeeks\cms\models\StorageFile;
use skeeks\cms\models\WidgetDescriptor;
use Yii;
use yii\base\Component;
use yii\helpers\ArrayHelper;
use yii\web\UploadedFile;

/**
 *
 * @method WidgetDescriptor[]   getComponents()
 * @method WidgetDescriptor     getComponent($id)
 *
 *
 * Class RegisteredWidgets
 * @package skeeks\cms\components
 */
class RegisteredWidgets extends CollectionComponents
{
    public $componentClassName  = 'skeeks\cms\models\WidgetDescriptor';

    /**
     *
     *
     * 'skeeks\cms\widgets\infoblocks\Infoblocks' =>
    [
        'label'         => 'Список инфоблоков',
        'description'   => 'Виджет который содержит в себе другие инфоблоки',
        'templates'     =>
        [
            'default' =>
            [
                'label' => 'Шаблон по умолчанию'
            ]
        ],
        'enabled'       => true
    ]
     *
     *
     * @var array
     */
    /**
     * @param $classNameWiget
     * @return WidgetDescriptor|null
     */
    public function getDescriptor($classNameWiget)
    {
        return $this->getComponent($classNameWiget);
    }
}