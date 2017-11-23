<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 02.06.2015
 */

namespace skeeks\cms\grid;

use yii\helpers\ArrayHelper;
use yii\widgets\Pjax;

/**
 * Class GridViewSortableTrait
 * @package skeeks\cms\modules\admin\traits
 */
trait GridViewPjaxTrait
{
    public $pjaxClassName = 'yii\widgets\Pjax';
    /**
     * @var bool влючить/выключить pjax навигацию
     */
    public $enabledPjax = true;
    /**
     * @var array
     */
    public $pjaxOptions = [];
    /**
     * @var Pjax для того чтобы потом можно было обратиться к объекту pjax.
     */
    public $pjax;

    protected $_pjaxCreated = false;

    public function pjaxBegin()
    {
        if ($this->enabledPjax) {
            if (!$this->pjax) {
                $this->_pjaxCreated = true;

                $pjaxClassName = $this->pjaxClassName;

                $this->pjax = $pjaxClassName::begin(ArrayHelper::merge([
                    'id' => 'sx-pjax-grid-' . $this->id,
                ], $this->pjaxOptions));
            }
        }
    }

    public function pjaxEnd()
    {
        if ($this->enabledPjax && $this->_pjaxCreated) {
            $pjaxClassName = $this->pjax->className();
            $pjaxClassName::end();
        }
    }
}
