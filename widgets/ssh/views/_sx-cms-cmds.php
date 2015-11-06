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
<div class="sx-cms-cmds">
    <pre>
        <?= \Yii::$app->console->execute("cd " . ROOT_DIR . " && php yii help;"); ?>
    </pre>
</div>