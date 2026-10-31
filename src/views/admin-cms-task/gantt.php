<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */
/* @var $this yii\web\View */

use skeeks\cms\models\CmsUser;
use skeeks\cms\widgets\admin\CmsTaskGanttWidget;

// An employee sees their own tasks; a head of department chooses among subordinates, an administrator — anyone.
$me = \Yii::$app->user->identity;
$users = CmsUser::find()->managedBy()->andWhere([CmsUser::tableName().'.is_active' => 1])->orderBy([CmsUser::tableName().'.first_name' => SORT_ASC])->all();
$usersById = [(int)$me->id => $me];
foreach ($users as $user) {
    $usersById[(int)$user->id] = $user;
}
$executorId = (int)\Yii::$app->request->get('executor_id', $me->id);
$user = $usersById[$executorId] ?? $me;

$widget = new CmsTaskGanttWidget(['user' => $user, 'users' => array_values($usersById), 'view' => $this]);
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
