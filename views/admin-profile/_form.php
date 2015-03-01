<?php
echo \Yii::$app->cms->moduleCms()->renderFile('admin-user/_form.php', [
    'model' => $model
]);