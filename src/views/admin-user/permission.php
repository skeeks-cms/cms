<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model yii\web\IdentityInterface */

?>
<div class="assignment-index">
    <div class="col-lg-5">
        <?= Yii::t('skeeks/cms', 'Avaliable') ?>:
        <?php
        echo Html::textInput('search_av', '', ['class' => 'role-search', 'data-target' => 'avaliable']) . '<br>';
        echo Html::listBox('roles', '', $avaliable, [
            'id' => 'avaliable',
            'multiple' => true,
            'size' => 20,
            'style' => 'width:100%']);
        ?>
    </div>
    <div class="col-lg-1">
        &nbsp;<br><br>
        <?php
        echo Html::a('>>', '#', ['class' => 'btn btn-success', 'data-action' => 'assign']) . '<br>';
        echo Html::a('<<', '#', ['class' => 'btn btn-success', 'data-action' => 'delete']) . '<br>';
        ?>
    </div>
    <div class="col-lg-5">
        <?= Yii::t('skeeks/cms', 'Assigned') ?>:
        <?php
        echo Html::textInput('search_asgn', '', ['class' => 'role-search', 'data-target' => 'assigned']) . '<br>';
        echo Html::listBox('roles', '', $assigned, [
            'id' => 'assigned',
            'multiple' => true,
            'size' => 20,
            'style' => 'width:100%']);
        ?>
    </div>
</div>
<?php
$this->render('_script',['id'=>$model->{$idField}]);
