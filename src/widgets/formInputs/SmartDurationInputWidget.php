<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 02.03.2015
 */

namespace skeeks\cms\widgets\formInputs;


use skeeks\cms\base\InputWidget;
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class SmartDurationInputWidget extends InputWidget
{
    static public $autoIdPrefix = "SmartTimeInputWidget";

    public $viewFile = 'smart-duration';
    /**
     * @var array
     */
    public $defaultOptions = [
        'type'  => 'text',
        'class' => 'form-control',
    ];
}
