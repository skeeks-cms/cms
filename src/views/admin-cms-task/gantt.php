<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */
/* @var $this yii\web\View */

echo $this->render('@skeeks/cms/views/admin-worker/gantt', ['context' => \skeeks\cms\widgets\admin\TaskViewContext::fromRequest()]);
