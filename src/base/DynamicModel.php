<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\base;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class DynamicModel extends \yii\base\DynamicModel {
    
    /**
     * @var string
     */
    public $formName = null;

    protected $_attributeLabels = [];
    
    /**
     * @return null|string
     */
    public function formName()
    {
        if ($this->formName === null) {
            return parent::formName();
        }

        return $this->formName;
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return $this->_attributeLabels;
    }

    /**
     * @param array $attributeLabels
     * @return $this
     */
    public function setAttributeLebels($attributeLabels = [])
    {
        $this->_attributeLabels = [];
        return $this;
    }

    /**
     * @param string $attribute
     * @param string $value
     * @return $this
     */
    public function setAttributeLebel(string $attribute, string $value)
    {
        $this->_attributeLabels[$attribute] = $value;
        return $this;
    }
}