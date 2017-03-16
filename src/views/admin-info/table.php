<?php
use yii\helpers\Html;
/* @var $caption string */
/* @var $values array */
?>


<?php if (empty($values)): ?>

    <p>Empty.</p>

<?php else: ?>

    <table class="table table-condensed table-bordered table-striped table-hover sx-table" style="table-layout: fixed;">
        <thead>
            <tr>
                <th><?=\Yii::t('skeeks/cms','Name')?></th>
                <th><?=\Yii::t('skeeks/cms','Value')?></th>
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
