<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */
/* @var $this yii\web\View */
/* @var $context \skeeks\cms\widgets\admin\TaskViewContext|null the task section; null — an employee's card */

use skeeks\cms\widgets\admin\CmsTaskGanttWidget;
use skeeks\cms\widgets\admin\TaskViewContext;

if (!isset($context) || !$context) {
    $context = TaskViewContext::forUser($this->context->action->model);
}

$widget = new CmsTaskGanttWidget(['context' => $context, 'view' => $this]);
if (\Yii::$app->request->get('sx-gantt-data')) {
    // The data after a move: only JSON, without the page around it.
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
    \Yii::$app->response->data = $widget->data();
    \Yii::$app->end();
}

echo $widget->run();
