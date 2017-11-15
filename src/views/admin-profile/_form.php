<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (�����)
 * @date 01.03.2016
 */
/* @var $this yii\web\View */

echo $this->render('@skeeks/cms/views/admin-user/_form', [
    'model' => $model,
    'relatedModel' => $relatedModel,
    'passwordChange' => $passwordChange,
]);
