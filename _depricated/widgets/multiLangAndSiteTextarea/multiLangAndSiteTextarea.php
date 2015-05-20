<?php
/**
 * multiLangAndSiteTextarea
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 19.11.2014
 * @since 1.0.0
 */

namespace skeeks\cms\widgets\formInputs\multiLangAndSiteTextarea;

use skeeks\cms\App;
use skeeks\cms\Exception;
use skeeks\cms\models\behaviors\HasFiles;
use skeeks\cms\models\behaviors\HasMultiLangAndSiteFields;
use skeeks\cms\models\Lang;
use skeeks\cms\models\Site;
use skeeks\cms\models\StaticBlock;
use skeeks\cms\validators\HasBehaviorsAnd;
use skeeks\sx\validate\Validate;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\widgets\InputWidget;
use Yii;

/**
 * Class Widget
 * @package skeeks\cms\widgets\formInputs\storageFiles
 */
class multiLangAndSiteTextarea extends InputWidget
{
    /**
     * @var HasFiles
     */
    protected $_behaviorFiles = null;

    /**
     * @var null|Lang
     */
    public $lang = null;

    /**
     * @var null|Site
     */
    public $site = null;

    /**
     * Берем поведения модели
     *
     */
    private function _initAndValidate()
    {
        if (!$this->hasModel())
        {
            throw new Exception("Этот файл рассчитан только для форм с моделью");
        }

        Validate::ensure(new HasBehaviorsAnd(HasMultiLangAndSiteFields::className()), $this->model);
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        try
        {
            $this->_initAndValidate();

            $value = '';
            $valueArray = Html::getAttributeValue($this->model, $this->attribute);

            $fieldName[] = Html::getInputName($this->model, $this->attribute);

            if ($this->site)
            {
                $this->model->setCurrentSite($this->site);
                $fieldName[] = '[' . $this->site->getCode() . ']';
            }

            if ($this->lang)
            {
                $this->model->setCurrentLang($this->lang);
                $fieldName[] = '[' . $this->lang->id . ']';
            }Т

            $value = $this->model->getMultiFieldValue($this->attribute);

            $fieldName[] = '[' . HasMultiLangAndSiteFields::DEFAULT_VALUE_SECTION . ']';

            echo Html::textarea(implode('', $fieldName), $value,
            [
                'class' => 'form-control',
            ]);

        } catch (Exception $e) {
            echo $e->getMessage();
        }

    }
}