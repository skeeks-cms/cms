<?php
/**
 * WidgetDescriptor
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 24.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\models;

use skeeks\cms\base\Component;
use skeeks\cms\base\Widget;
use skeeks\cms\components\ModelActionViews;
use skeeks\cms\models\ComponentModel;

class WidgetDescriptor
    extends ComponentModel
{
    public $description = "";
    public $templates   = [];

    /**
     * @var ModelActionViews
     */
    protected $_templatesObject = null;
    /**
     * @return ModelActionViews
     */
    public function getTemplatesObject()
    {
        if ($this->_templatesObject === null)
        {
            $this->_templatesObject = new ModelActionViews([
                'components' => $this->templates
            ]);
        }

        return $this->_templatesObject;
    }


}