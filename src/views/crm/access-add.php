<?php
/**
 * «Добавить сотрудника» in the standard backend window: the employee, the
 * level as three cards with one line each («Свои сделки» by default),
 * «Добавить» and «Отмена». The post goes to the same action; on success the
 * window closes and the card reloads (lead-identity.js, data-sx-lead-ajax-form).
 * ClientTransferActions::clientAccessAdd().
 *
 * @var yii\web\View $this
 * @var skeeks\cms\models\CmsCompany|skeeks\cms\models\CmsUser $model
 */

use skeeks\cms\assets\admin\LeadIdentityAsset;
use skeeks\cms\crm\CrmAccess;
use skeeks\cms\models\CmsUser;
use skeeks\cms\widgets\AjaxSelectModel;
use yii\helpers\Html;
use yii\helpers\Url;

LeadIdentityAsset::register($this);
$hints = CrmAccess::levelHints();
?>
<?= Html::beginForm(Url::current(), 'post', ['data-sx-lead-ajax-form' => true, 'class' => 'sx-crm-access-add']); ?>
    <div class="form-group">
        <label for="sx-crm-access-add-user">Сотрудник</label>
        <?= AjaxSelectModel::widget([
            'id' => 'sx-crm-access-add-user',
            'name' => 'user_id',
            'modelClass' => CmsUser::class,
            'modelShowAttribute' => 'shortDisplayName',
            'searchQuery' => static function ($word = '') {
                $query = CmsUser::find()->isWorker()->cmsSite()->andWhere([CmsUser::tableName().'.is_active' => 1]);

                return $word ? $query->search($word) : $query;
            },
            'options' => ['placeholder' => 'Найдите сотрудника'],
        ]); ?>
    </div>
    <fieldset class="sx-crm-access-levels">
        <legend>Уровень доступа</legend>
        <?php foreach (CrmAccess::levels() as $level => $name) : ?>
            <label class="sx-crm-access-level">
                <?= Html::radio('level', $level === CrmAccess::LEVEL_DEAL, ['value' => $level]); ?>
                <span class="sx-crm-access-level__name"><?= Html::encode($name); ?></span>
                <span class="sx-crm-access-level__hint"><?= Html::encode($hints[$level] ?? ''); ?></span>
            </label>
        <?php endforeach; ?>
    </fieldset>
    <?php // A client without the main one (the pool): the first participant — the one who adds is the main one, or this employee. ?>
    <?php if (CrmAccess::mainReady() && CrmAccess::instance()->mainResponsibleId($model instanceof \skeeks\cms\models\CmsCompany ? 'company' : 'client', (int)$model->id) === null) : ?>
        <div class="form-check">
            <?= Html::checkbox('make_main', false, ['id' => 'sx-crm-access-add-main', 'value' => 1, 'class' => 'form-check-input']); ?>
            <label class="form-check-label" for="sx-crm-access-add-main">Сделать основным ответственным</label>
            <p class="sx-lead-identity__note">У клиента нет основного ответственного. Без отметки основным станете вы, а сотрудник получит выбранный уровень.</p>
        </div>
    <?php endif; ?>
    <div class="sx-lead-hint__actions">
        <?= Html::submitButton('Добавить', ['class' => 'sx-button sx-button--primary']); ?>
        <?= Html::button('Отмена', [
            'class' => 'sx-button sx-button--secondary',
            // As the standard form buttons (cms-backend form-buttons.js): close the backend window.
            'onclick' => 'if (sx.Window && sx.Window.openerWidget()) { sx.Window.openerWidget().close(); } else { history.back(); } return false;',
        ]); ?>
    </div>
<?= Html::endForm(); ?>
