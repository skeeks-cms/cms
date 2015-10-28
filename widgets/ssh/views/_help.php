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
    <p><code>php yii help</code> - <?=\Yii::t('app','see help')?></p>
    <p><code>php yii help <?= htmlspecialchars('<cmd-name>')?></code>  - <?=\Yii::t('app','see help a certain command')?></p>
    <p><code>php yii help cms/update</code> - <?=\Yii::t('app','example help of a sertain controller')?></p>
    <p><code>php yii help cms/update/all</code> - <?=\Yii::t('app','example help of a sertain action')?></p>
    <p><code>php yii cms/update/all</code> - <?=\Yii::t('app','complete update of  project')?></p>
    <p><code>php yii cms/composer/status</code> - <?=\Yii::t('app','watch the kernel modification')?></p>
    <p><code>php yii cms/composer/require skeeks/cms-module-form2</code> - <?=\Yii::t('app','installing package')?></p>
    <p><code>php yii cms/composer/update --dry-run</code> - <?=\Yii::t('app','check the installation or update')?></p>
</div>
