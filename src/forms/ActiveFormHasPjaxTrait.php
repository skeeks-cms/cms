<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\forms;

use skeeks\cms\widgets\Pjax;
use yii\base\WidgetEvent;
use yii\helpers\ArrayHelper;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
trait ActiveFormHasPjaxTrait
{
    /**
     * @var bool
     */
    public $usePjax = true;
    /**
     * @var array
     */
    public $pjaxOptions = [];

    /**
     * @var string
     */
    public $pjaxClass = Pjax::class;

    /**
     *
     */
    protected function _initPjax()
    {
        if ($this->usePjax === false) {
            return;
        }

        $this->options = ArrayHelper::merge($this->options, [
            'data-pjax' => true,
        ]);

        $pjaxClass = $this->pjaxClass;

        $pjax = $pjaxClass::begin(ArrayHelper::merge([
            'id'              => 'sx-pjax-form-'.$this->id,
            'enablePushState' => false,
            'isShowError'     => true,
        ], $this->pjaxOptions));

        $this->on(self::EVENT_AFTER_RUN, function (WidgetEvent $event) use ($pjaxClass) {

            echo $event->result;
            $pjaxClass::end();

            $content = ob_get_clean();
            $event->result = $content;

            return $event;
        });

    }
}