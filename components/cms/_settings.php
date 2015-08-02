<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 27.03.2015
 */
use yii\helpers\Html;
use skeeks\cms\modules\admin\widgets\form\ActiveFormUseTab as ActiveForm;

/* @var $this yii\web\View */
/* @var $model \skeeks\cms\models\WidgetConfig */

?>
<?php $form = ActiveForm::begin(); ?>


<?= $form->fieldSet('Безопасность'); ?>
    <?= $form->fieldSelect($model, 'sessionType', [
        \skeeks\cms\components\CmsSettings::SESSION_FILE    => 'В файлах',
        \skeeks\cms\components\CmsSettings::SESSION_DB      => 'В базе данных',
    ])->hint('Хранилище сессий'); ?>
<?= $form->fieldSetEnd(); ?>

<?= $form->buttonsCreateOrUpdate($model); ?>
<?php ActiveForm::end(); ?>


