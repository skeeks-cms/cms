<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 02.03.2016
 */

namespace skeeks\cms\base;

use yii\widgets\ActiveForm;

/**
 *
 * Класс который умеет стоить форму для себя
 *
 * Interface ConfigFormInterface
 * @package yii\base
 */
interface ConfigFormInterface
{
    /**
     * @deprecated
     * @return string the view path that may be prefixed to a relative view name.
     */
    public function renderConfigForm(ActiveForm $form);
}
