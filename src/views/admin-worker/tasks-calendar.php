<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */
/* @var $this yii\web\View */
/* @var $model \skeeks\cms\models\CmsUser the employee whose tasks are shown */

if (!isset($model) || !$model) {
    $model = $this->context->action->model;
}

echo \skeeks\cms\widgets\admin\CmsTaskCalendarWidget::widget(['user' => $model]);
