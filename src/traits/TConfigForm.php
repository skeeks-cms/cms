<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\traits;

use yii\widgets\ActiveForm;
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
trait TConfigForm
{
    
    /**
     * @return ActiveForm
     */
    public function beginConfigForm()
    {
        return ActiveForm::begin();
    }


    /**
     * @return array
     */
    public function getConfigFormFields()
    {
        return [];
    }


    /**
     * @var string 
     */
    public $configFormView = "";
    /**
     *
     * @param ActiveForm $form
     * @return string
     */
    public function renderConfigFormFields(ActiveForm $form)
    {
        $formContent = '';

        if ($fields = $this->getConfigFormFields()) {
            $formContent = (new \skeeks\yii2\form\Builder([
                'models'     => $this->getConfigFormModels(),
                'model'      => $this,
                'activeForm' => $form,
                'fields'     => $fields,
            ]))->render();
        } else {
            if ($this->configFormView) {
                $formContent = \Yii::$app->view->render($this->configFormView, [
                    'model' => $this,
                    'form' => $form,
                ]);
            }
        }
        
        return $formContent;
    }

    /**
     * @return array
     */
    public function getConfigFormModels()
    {
        return [];
    }
}