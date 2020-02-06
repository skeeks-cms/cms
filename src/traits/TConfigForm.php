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
     * @depricated
     *
     * @param ActiveForm $form
     * @return string
     */
    public function renderConfigForm(ActiveForm $form)
    {
        return $this->renderConfigFormFields($form);
    }

    /**
     *
     * @param ActiveForm $form
     * @return string
     */
    public function renderConfigFormFields(ActiveForm $form)
    {
        return '';
    }

    /**
     * @return array
     */
    public function getConfigFormModels()
    {
        return [];
    }
}