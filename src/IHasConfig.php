<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link https://skeeks.com/
 * @copyright (c) 2010 SkeekS
 * @date 09.03.2018
 */

namespace skeeks\cms;

use skeeks\yii2\form\FormFieldsBuilder;

/**
 * 
 * echo (new \skeeks\yii2\form\FormFieldsBuilder([
 *      'model' => $this,
 *      'models' => $this->getConfigFormModels(),
 *      'fields' => $this->getConfigFormFields(),
 * ]))->render(); ?>
 * 
 * 
 * Interface IHasConfig
 * @package skeeks\cms
 */
interface IHasConfig
{
    /**
     * @see FormFieldsBuilder
     * @return string
     */
    //public function getConfigFormFields();

    /**
     * @see FormFieldsBuilder
     * @return array
     */
    //public function getConfigFormModels();
}