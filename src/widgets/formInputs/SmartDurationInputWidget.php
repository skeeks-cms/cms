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
    public $availableUnits = [
        'sec'  => 'сек',
        'min'  => 'мин',
        'hour' => 'час',
    ];
    /**
     * @var string
     */
    public $defaultUnit = 'sec';
    /**
     * @var array
     */
    public $defaultOptions = [
        'type'  => 'text',
        'class' => 'form-control',
    ];

    public function init()
    {
        if (!$this->availableUnits) {
            $this->availableUnits = [
                'sec' => 'сек',
            ];
        }

        if (!isset($this->availableUnits[$this->defaultUnit])) {
            foreach ($this->availableUnits as $unit => $label) {
                $this->defaultUnit = $unit;
                break;
            }
        }

        parent::init();
    }
}
