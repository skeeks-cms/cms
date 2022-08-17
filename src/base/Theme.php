<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 27.03.2015
 */

namespace skeeks\cms\base;

use skeeks\cms\IHasConfigForm;
use skeeks\cms\traits\HasComponentDescriptorTrait;
use skeeks\cms\traits\TConfigForm;
use skeeks\yii2\config\ConfigBehavior;
use skeeks\yii2\config\ConfigTrait;
use skeeks\yii2\config\DynamicConfigModel;
use yii\helpers\ArrayHelper;

/**
 * @property DynamicConfigModel $configFormModel
 * @property array $treeViews
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 */
abstract class Theme extends \yii\base\Theme
{
    use HasComponentDescriptorTrait;
    //use TConfigForm;

    /**
     * Для каких сайтов доступна эта тема
     * @var array
     */
    public $site_ids = [];

    public $tree_views = [];
    /**
     * @return array
     */
    public function _getDefaultTreeViews()
    {
        return [
            'home' => [
                'name' => 'Главная страница',
                'description' => ''
            ],
            'text' => [
                'name' => 'Текстовый раздел',
                'description' => 'Стандартное оформление раздела с текстом'
            ],
            'contacts' => [
                'name' => 'Страница контактов',
                'description' => 'Страница с картой, временем работы и контактами'
            ],
        ];
    }

    /**
     * @return array
     */
    public function getTreeViews()
    {
        return ArrayHelper::merge($this->_getDefaultTreeViews(), $this->tree_views);
    }

    /**
     * @var null
     */
    protected $_configFormModel = null;

    /**
     * @return DynamicConfigModel
     * @throws \yii\base\InvalidConfigException
     */
    public function getConfigFormModel()
    {
        if ($this->_configFormModel === null) {
            $data = $this->getConfigFormModelData();
            $data["class"] = DynamicConfigModel::class;

            $this->_configFormModel = \Yii::createObject($data);
            //Установить значения из темы
            $data = ArrayHelper::toArray($this);
            $this->_configFormModel->setAttributes($data);
        }

        return $this->_configFormModel;
    }

    /**
     * Данные для формы настроек
     *
     * @return array
     */
    public function getConfigFormModelData()
    {
        return [];
    }
}

