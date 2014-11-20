<?php
/**
 * Универсальная опция страницы
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 17.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\components;

use skeeks\cms\models\behaviors\HasPageOptions;
use skeeks\cms\models\PageOption;
use skeeks\cms\validators\HasBehaviorsAnd;
use skeeks\sx\validate\Validate;
use Yii;
use yii\base\Model;

/**
 * @method PageOption[]   getComponents()
 * @method PageOption     getComponent($id)
 *
 * Class CollectionComponents
 * @package skeeks\cms\components
 */
class PageOptions extends CollectionComponents
{
    public $componentClassName  = 'skeeks\cms\models\PageOption';

    /**
     *
     * Установка значения опция из модели, которая умеет хранить в себе настройки pageOptions
     *
     * @param Model $model
     * @return $this
     * @throws \skeeks\sx\validate\Exception
     */
    public function setValuesFromModel(Model $model)
    {
        Validate::ensure(new HasBehaviorsAnd(HasPageOptions::className()), $model);

        if ($modelPageOptionValues = $model->getMultiPageOptionsData())
        {
            foreach ($modelPageOptionValues as $idPageOption => $value)
            {
                $this->getComponent($idPageOption)->getValue()->setAttributes((array) $value);
            }
        }

        return $this;
    }



}