<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 02.03.2015
 */
namespace skeeks\cms\modules\admin\widgets\formInputs;
use skeeks\cms\models\CmsContentElement;
use yii\base\Exception;

/**
 * @property $cmsContentElement CmsContentElement
 *
 * Class CmsContentElementInput
 * @package skeeks\cms\modules\admin\widgets\formInputs
 */
class CmsContentElementInput extends SelectModelDialogInput
{
    /**
     * @var string
     */
    public $baseRoute = 'cms/tools/select-cms-element';

    /**
     * @var string
     */
    public $viewFile  = 'cms-content-element-input';

    /**
     * @return CmsContentElement
     */
    public function getModelData()
    {
        if ($id = $this->model->{$this->attribute})
        {
            return CmsContentElement::findOne($id);
        }
    }
}