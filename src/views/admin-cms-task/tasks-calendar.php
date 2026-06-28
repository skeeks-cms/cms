<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

$model = \Yii::$app->user->identity;
?>
<?php echo $this->render('@skeeks/cms/views/admin-worker/tasks-calendar', ['model' => $model]); ?>
