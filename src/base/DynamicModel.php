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
}