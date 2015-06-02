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

    <table class="table table-condensed table-bordered table-striped table-hover sx-table" style="table-layout: fixed;">
        <thead>
            <tr>
                <th>Название</th>
                <th>Значение</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($values as $name => $value): ?>
            <tr>
                <th><?= Html::encode($name) ?></th>
                <td style="overflow:auto"><?= $value ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

<?php endif; ?>
