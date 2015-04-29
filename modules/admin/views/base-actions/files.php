<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 29.04.2015
 */

/* @var $model \skeeks\cms\models\Publication */
/* @var $this yii\web\View */
?>
<?= \skeeks\cms\modules\admin\widgets\StorageFilesForModel::widget([
    'model' => $model,
]); ?>