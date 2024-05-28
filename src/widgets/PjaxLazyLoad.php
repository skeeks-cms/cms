<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 19.12.2016
 */

namespace skeeks\cms\widgets;

/**
 * @property bool isPjax;
 *
 * @package skeeks\cms\widgets
 */
class PjaxLazyLoad extends Pjax
{
    /**
     * @var int
     */
    public $delay = 200;


    public static $autoIdPrefix = 'PjaxLazyLoad';

    /**
     * @return bool
     */
    public function getIsPjax() {
        return $this->requiresPjax();
    }

    /**
     * @return string|void
     */
    public function run()
    {
        if (!$this->isPjax) {
            \Yii::$app->view->registerJs(<<<JS
    setTimeout(function() {
        $.pjax.reload("#{$this->id}", {
            'timeout': $this->timeout,
            async: true
        });
        $.pjax.xhr = null;
    }, $this->delay);
JS
            );
        }

        parent::run();
    }
}