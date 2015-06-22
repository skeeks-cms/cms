<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 23.06.2015
 */
?>

<div class="sx-widget-ssh-console">

    <iframe style="border: none; width: 100%; height: 600px;" src="<?= \skeeks\cms\helpers\UrlHelper::construct('/admin/ssh/console')->enableAdmin()->toString(); ?>"></iframe>
    <div class="sx-bg-primary">
        <p><code>php yii help</code></p>
        <p><code>php yii help <?= htmlspecialchars('<cmd name>')?></code></p>
        <p><code>php yii help cms/update/all</code></p>
    </div>
    <pre>
        <? system("cd " . ROOT_DIR . " && php yii help;"); ?>
    </pre>
</div>