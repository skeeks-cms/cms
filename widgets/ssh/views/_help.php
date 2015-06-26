<?
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 26.06.2015
 */
/* @var $this yii\web\View */
/* @var $widget \skeeks\cms\widgets\SshConsoleWidget */
?>
<div class="sx-fast-cmd">
    <p><code>php yii help</code> - посмотреть хелп</p>
    <p><code>php yii help <?= htmlspecialchars('<cmd-name>')?></code>  - посмотреть хелп определлной команды</p>
    <p><code>php yii help cms/update</code> - пример хелпа определнного контроллера</p>
    <p><code>php yii help cms/update/all</code> - пример хелпа определнного действия</p>
    <p><code>php yii cms/update/all</code> - запуск действия</p>
    <p><code>php yii cms/composer/status</code> - запуск действия</p>
    <p><code>php yii cms/composer/require skeeks/cms-module-form2</code> - установка пакета</p>
    <p><code>php yii cms/composer/update --pry-run</code> - проверка установки или обновления</p>
</div>
