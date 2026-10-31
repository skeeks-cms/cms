<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */
/* @var $this yii\web\View */

echo \skeeks\cms\widgets\admin\CmsTaskDeadlinesWidget::widget(['user' => \Yii::$app->user->identity]);
