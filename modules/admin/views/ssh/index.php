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
        <p><code>php yii help</code> - посмотреть хелп</p>
        <p><code>php yii help <?= htmlspecialchars('<cmd-name>')?></code>  - посмотреть хелп определлной команды</p>
        <p><code>php yii help cms/update</code> - пример хелпа определнного контроллера</p>
        <p><code>php yii help cms/update/all</code> - пример хелпа определнного действия</p>
        <p><code>php yii cms/update/all</code> - запуск действия</p>
        <p><code>php yii cms/composer/status</code> - запуск действия</p>
        <p><code>php yii cms/composer/require skeeks/cms-module-form2</code> - установка пакета</p>
        <p><code>php yii cms/composer/update --pry-run</code> - проверка установки или обновления</p>
    </div>
    <pre>
        <? system("cd " . ROOT_DIR . " && php yii help;"); ?>
    </pre>



</div>