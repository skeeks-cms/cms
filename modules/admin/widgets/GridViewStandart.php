<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 24.07.2015
 */
namespace skeeks\cms\modules\admin\widgets;
use skeeks\cms\modules\admin\grid\CheckboxColumn;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * Class GridViewStandart
 * @package skeeks\cms\modules\admin\widgets
 */
class GridViewStandart extends GridViewHasSettings
{
    public $adminController = null;

    public function init()
    {
        $this->columns = ArrayHelper::merge([
            ['class' => 'skeeks\cms\modules\admin\grid\CheckboxColumn'],
            [
                'class'         => \skeeks\cms\modules\admin\grid\ActionColumn::className(),
                'controller'    => $this->adminController
            ],
            [
                'class' => 'yii\grid\SerialColumn',
                'visible' => false
            ],
        ], $this->columns);

        parent::init();
    }
}