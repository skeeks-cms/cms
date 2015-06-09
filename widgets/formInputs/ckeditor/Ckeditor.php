<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.04.2015
 */
namespace skeeks\cms\widgets\formInputs\ckeditor;

use skeeks\cms\Exception;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\behaviors\HasFiles;
use skeeks\cms\validators\HasBehavior;
use skeeks\sx\validate\Validate;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\widgets\InputWidget;
use Yii;

/**
 * Class Ckeditor
 * @package skeeks\cms\widgets\formInputs\ckeditor
 */
class Ckeditor extends \skeeks\widget\ckeditor\CKEditor
{
    /**
     * @var Модель к которой привязываются файлы
     */
    public $relatedModel;


    protected function initOptions()
    {
        parent::initOptions();

        $additionalData = [];
        if ($this->relatedModel && ($this->relatedModel instanceof ActiveRecord && !$this->relatedModel->isNewRecord))
        {
            if (Validate::isValid(new HasBehavior(HasFiles::className()), $this->relatedModel))
            {
                $additionalData = $this->relatedModel->getRef()->toArray();
            }
        }

        $this->clientOptions['filebrowserImageBrowseUrl'] = UrlHelper::construct('cms/tools/select-file', $additionalData)
            ->setSystemParam(\skeeks\cms\modules\admin\Module::SYSTEM_QUERY_EMPTY_LAYOUT, 'true')
            ->enableAdmin()
            ->toString();
    }
}
