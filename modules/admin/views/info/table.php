<?php
use yii\helpers\Html;
use Yii;
/* @var $caption string */
/* @var $values array */
?>

<h3><?= $caption ?></h3>

<?php if (empty($values)): ?>

    <p>Empty.</p>

<?php else: ?>

    <table class="table table-condensed table-bordered table-striped table-hover" style="table-layout: fixed;">
        <thead>
            <tr>
                <th style="width: 200px;">Название</th>
                <th>Значение</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($values as $name => $value): ?>
            <tr>
                <th style="width: 200px;"><?= Html::encode($name) ?></th>
                <td style="overflow:auto"><?= Html::encode($value) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

<?php endif; ?>
