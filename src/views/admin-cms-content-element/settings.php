<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 02.06.2015
 */
/* @var $this yii\web\View */
/* @var $action \skeeks\cms\modules\admin\actions\modelEditor\AdminModelEditorAction */
/* @var $controller \skeeks\cms\controllers\AdminCmsContentElementController  */
$controller = $action->controller;
?>
<?= $this->render('@skeeks/cms/views/admin-cms-content/_form.php', [
    'model' => $controller->content
]); ?>
