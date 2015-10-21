<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 30.04.2015
 */
namespace skeeks\cms\relatedProperties\propertyTypes;
use skeeks\cms\components\Cms;
use skeeks\cms\models\CmsContentElement;
use skeeks\cms\relatedProperties\PropertyType;
use yii\helpers\ArrayHelper;

/**
 * Class PropertyTypeElement
 * @package skeeks\cms\relatedProperties\propertyTypes
 */
class PropertyTypeElement extends PropertyType
{
    public $code = self::CODE_ELEMENT;
    public $name = "";


    public $content_id;

    public function init()
    {
        parent::init();

        if(!$this->name)
        {
            $this->name = \Yii::t('app','Binding to an element');
        }
    }

    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(),
        [
            'content_id'  => \Yii::t('app','Content'),
        ]);
    }

    public function rules()
    {
        return ArrayHelper::merge(parent::rules(),
        [
            ['content_id', 'integer'],
        ]);
    }

    /**
     * @return \yii\widgets\ActiveField
     */
    public function renderForActiveForm()
    {
        $field = parent::renderForActiveForm();

        $find = CmsContentElement::find()->active();

        if ($this->content_id)
        {
            $find->andWhere(['content_id' => $this->content_id]);
        }

        if ($this->multiple == Cms::BOOL_N)
        {
            $field = $this->activeForm->fieldSelect(
                $this->model->relatedPropertiesModel,
                $this->property->code,
                ArrayHelper::map($find->all(), 'id', 'name'),
                []
            );
        } else if ($this->multiple == Cms::BOOL_Y)
        {
            $field = $this->activeForm->fieldSelectMulti(
                $this->model->relatedPropertiesModel,
                $this->property->code,
                ArrayHelper::map($find->all(), 'id', 'name'),
                []
            );
        }

        return $field;
    }


    /**
     * Файл с формой настроек, по умолчанию лежит в той же папке где и компонент.
     *
     * @return string
     */
    public function getConfigFormFile()
    {
        $class = new \ReflectionClass($this->className());
        return dirname($class->getFileName()) . DIRECTORY_SEPARATOR . 'views/_formPropertyTypeElement.php';
    }
}