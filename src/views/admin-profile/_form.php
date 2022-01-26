<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (�����)
 * @date 01.03.2016
 */
/* @var $this yii\web\View */

?>
<?php if (!$model->email || !$model->first_name || !$model->last_name 
    //|| !$model->image
) : ?>
    <?php $alert = \yii\bootstrap\Alert::begin([
        'options' => [
            'class' => 'alert-danger',
        ],
    ]); ?>
    <p><b>Внимание!</b></p>
    <p>Для продолжения работы с системой управления сайта требуется указать ваши данные:</p>
    <ul>
        <li>Реальный email</li>
        <li>Фамилия</li>
        <li>Имя</li>
        <li>Фото</li>
    </ul>

    <?php $alert::end(); ?>
<?php endif; ?>

<?php
echo $this->render('@skeeks/cms/views/admin-user/_form', [
    'model'          => $model,
    'relatedModel'   => $relatedModel,
    'passwordChange' => $passwordChange,
]);
?>