<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */
/* @var $this yii\web\View */

$context = \skeeks\cms\widgets\admin\TaskViewContext::fromRequest();
echo \skeeks\cms\widgets\admin\CmsUnassignedClientTasksWidget::widget(['user' => $context->user]);
echo \skeeks\cms\widgets\admin\CmsTaskCalendarWidget::widget(['context' => $context]);
