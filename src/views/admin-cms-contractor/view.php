<?php
/**
 * @var yii\web\View $this
 * @var \skeeks\cms\models\CmsContractor $model
 */

use skeeks\cms\backend\widgets\AjaxControllerActionsWidget;
use yii\helpers\Html;

$controller = $this->context;
$action = $controller->action;
$model = $action->model;

$formatValue = static function ($value, $empty = 'Не указано') {
    $value = trim((string)$value);

    return $value === ''
        ? '<span class="sx-contractor-muted">'.Html::encode($empty).'</span>'
        : Html::encode($value);
};

$entityCard = static function ($controllerId, $entity, $label, $title, $subtitle, $icon) {
    $content = '<div class="sx-contractor-entity">'
        .'<div class="sx-contractor-entity-icon"><i class="'.$icon.'"></i></div>'
        .'<div>'
        .'<div class="sx-contractor-label">'.Html::encode($label).'</div>'
        .'<div class="sx-contractor-entity-title">'.Html::encode($title).'</div>';

    if ($subtitle) {
        $content .= '<div class="sx-contractor-entity-subtitle">'.Html::encode($subtitle).'</div>';
    }

    $content .= '</div></div>';

    return AjaxControllerActionsWidget::widget([
        'controllerId'            => $controllerId,
        'modelId'                 => $entity->id,
        'isRunFirstActionOnClick' => true,
        'content'                 => $content,
        'options'                 => [
            'class' => 'sx-contractor-entity-link',
        ],
    ]);
};

$hasPrintAssets = $model->cmsImage || $model->stamp || $model->directorSignature || $model->signatureAccountant;

$this->registerCss(<<<CSS
.sx-contractor-card {
    background: #fff;
    border: 1px solid #e3e7eb;
    border-radius: .625rem;
    overflow: hidden;
}
.sx-contractor-section {
    padding: 1.4rem 1.75rem;
    border-bottom: 1px solid #edf0f2;
}
.sx-contractor-section:last-child {
    border-bottom: 0;
}
.sx-contractor-section-title {
    margin: 0 0 .9rem;
    font-size: 1.125rem;
    font-weight: 600;
}
.sx-contractor-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: .75rem;
}
.sx-contractor-grid.is-two-columns {
    grid-template-columns: repeat(2, minmax(0, 1fr));
}
.sx-contractor-info,
.sx-contractor-bank,
.sx-contractor-media {
    padding: .9rem;
    border: 1px solid #e3e7eb;
    border-radius: .5rem;
    background: #fff;
    min-width: 0;
}
.sx-contractor-label {
    color: #8a929a;
    font-size: .75rem;
    margin-bottom: .3rem;
}
.sx-contractor-value,
.sx-contractor-bank-value {
    color: #303942;
    font-weight: 600;
    overflow-wrap: anywhere;
}
.sx-contractor-bank-title {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
    margin-bottom: .85rem;
}
.sx-contractor-bank-state {
    color: #188b38;
    font-size: .75rem;
    white-space: nowrap;
}
.sx-contractor-bank-state.is-disabled {
    color: #8a929a;
}
.sx-contractor-bank-details {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: .75rem;
}
.sx-contractor-bank-value {
    font-size: .875rem;
}
.sx-contractor-entity-link {
    display: block;
    color: inherit;
    text-decoration: none;
    cursor: pointer;
}
.sx-contractor-entity-link:hover,
.sx-contractor-entity-link:focus {
    color: inherit;
    text-decoration: none;
    outline: none;
}
.sx-contractor-entity {
    min-height: 5.25rem;
    height: 100%;
    padding: .9rem;
    border: 1px solid #e3e7eb;
    border-radius: .5rem;
    display: flex;
    align-items: flex-start;
    gap: .75rem;
    background: #fff;
    transition: border-color .15s ease, box-shadow .15s ease;
}
.sx-contractor-entity-link:hover .sx-contractor-entity,
.sx-contractor-entity-link:focus .sx-contractor-entity {
    border-color: #9dc8f0;
    box-shadow: 0 .5rem 1.5rem rgba(31, 82, 130, .08);
}
.sx-contractor-entity-icon {
    width: 2.125rem;
    height: 2.125rem;
    border-radius: 50%;
    background: #eef3f7;
    color: #607080;
    display: flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
}
.sx-contractor-entity-title {
    font-weight: 600;
    overflow-wrap: anywhere;
}
.sx-contractor-entity-subtitle {
    color: #606a73;
    font-size: .8rem;
    margin-top: .25rem;
    overflow-wrap: anywhere;
}
.sx-contractor-media {
    display: flex;
    min-height: 9rem;
    flex-direction: column;
}
.sx-contractor-media-preview {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding-top: .75rem;
}
.sx-contractor-media-preview img {
    display: block;
    max-width: 100%;
    max-height: 8rem;
    object-fit: contain;
}
.sx-contractor-description {
    margin: 0;
    color: #4d5963;
    white-space: pre-wrap;
    overflow-wrap: anywhere;
}
.sx-contractor-muted {
    color: #a5adb5;
    font-weight: 400;
}
@media (max-width: 68.75rem) {
    .sx-contractor-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
@media (max-width: 43.75rem) {
    .sx-contractor-grid,
    .sx-contractor-grid.is-two-columns,
    .sx-contractor-bank-details {
        grid-template-columns: 1fr;
    }
    .sx-contractor-section {
        padding: 1.1rem;
    }
}
CSS
);
?>

<div class="sx-contractor-card">
    <section class="sx-contractor-section">
        <div class="sx-contractor-grid">
            <div class="sx-contractor-info">
                <div class="sx-contractor-label">Тип</div>
                <div class="sx-contractor-value"><?= $formatValue($model->typeAsText); ?></div>
            </div>
            <div class="sx-contractor-info">
                <div class="sx-contractor-label">ИНН</div>
                <div class="sx-contractor-value"><?= $formatValue($model->inn); ?></div>
            </div>
            <div class="sx-contractor-info">
                <div class="sx-contractor-label">КПП</div>
                <div class="sx-contractor-value"><?= $formatValue($model->kpp); ?></div>
            </div>
            <div class="sx-contractor-info">
                <div class="sx-contractor-label">ОГРН / ОГРНИП</div>
                <div class="sx-contractor-value"><?= $formatValue($model->ogrn); ?></div>
            </div>
            <div class="sx-contractor-info">
                <div class="sx-contractor-label">Краткое наименование</div>
                <div class="sx-contractor-value"><?= $formatValue($model->asShortText); ?></div>
            </div>
            <div class="sx-contractor-info">
                <div class="sx-contractor-label">Полное наименование</div>
                <div class="sx-contractor-value"><?= $formatValue($model->full_name); ?></div>
            </div>
            <div class="sx-contractor-info">
                <div class="sx-contractor-label">Международное наименование</div>
                <div class="sx-contractor-value"><?= $formatValue($model->international_name); ?></div>
            </div>
            <div class="sx-contractor-info">
                <div class="sx-contractor-label">ОКПО</div>
                <div class="sx-contractor-value"><?= $formatValue($model->okpo); ?></div>
            </div>
            <div class="sx-contractor-info">
                <div class="sx-contractor-label">Назначение</div>
                <div class="sx-contractor-value">
                    <?= $model->is_our ? 'Наши реквизиты' : 'Реквизиты контрагента'; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="sx-contractor-section">
        <h3 class="sx-contractor-section-title">Адреса и контакты</h3>
        <div class="sx-contractor-grid is-two-columns">
            <div class="sx-contractor-info">
                <div class="sx-contractor-label">Юридический адрес</div>
                <div class="sx-contractor-value"><?= $formatValue($model->address); ?></div>
            </div>
            <div class="sx-contractor-info">
                <div class="sx-contractor-label">Почтовый адрес</div>
                <div class="sx-contractor-value"><?= $formatValue(trim($model->mailing_postcode.' '.$model->mailing_address)); ?></div>
            </div>
            <div class="sx-contractor-info">
                <div class="sx-contractor-label">Телефон</div>
                <div class="sx-contractor-value">
                    <?= $model->phone ? Html::a(Html::encode($model->phone), 'tel:'.$model->phone) : $formatValue(''); ?>
                </div>
            </div>
            <div class="sx-contractor-info">
                <div class="sx-contractor-label">Email</div>
                <div class="sx-contractor-value">
                    <?= $model->email ? Html::mailto(Html::encode($model->email), $model->email) : $formatValue(''); ?>
                </div>
            </div>
        </div>
    </section>

    <?php if ($model->banks) : ?>
        <section class="sx-contractor-section">
            <h3 class="sx-contractor-section-title">Банковские реквизиты</h3>
            <div class="sx-contractor-grid is-two-columns">
                <?php foreach ($model->banks as $bank) : ?>
                    <div class="sx-contractor-bank">
                        <div class="sx-contractor-bank-title">
                            <div class="sx-contractor-value"><?= Html::encode($bank->bank_name); ?></div>
                            <span class="sx-contractor-bank-state<?= $bank->is_active ? '' : ' is-disabled'; ?>">
                                <i class="fa <?= $bank->is_active ? 'fa-check' : 'fa-ban'; ?>"></i>
                                <?= $bank->is_active ? 'Активен' : 'Отключен'; ?>
                            </span>
                        </div>
                        <div class="sx-contractor-bank-details">
                            <div>
                                <div class="sx-contractor-label">БИК</div>
                                <div class="sx-contractor-bank-value"><?= $formatValue($bank->bic); ?></div>
                            </div>
                            <div>
                                <div class="sx-contractor-label">Расчетный счет</div>
                                <div class="sx-contractor-bank-value"><?= $formatValue($bank->checking_account); ?></div>
                            </div>
                            <div>
                                <div class="sx-contractor-label">Корреспондентский счет</div>
                                <div class="sx-contractor-bank-value"><?= $formatValue($bank->correspondent_account); ?></div>
                            </div>
                            <div>
                                <div class="sx-contractor-label">Адрес банка</div>
                                <div class="sx-contractor-bank-value"><?= $formatValue($bank->bank_address); ?></div>
                            </div>
                        </div>
                        <?php if (trim((string)$bank->comment) !== '') : ?>
                            <div class="sx-contractor-label" style="margin-top: .75rem;">Комментарий</div>
                            <div class="sx-contractor-bank-value"><?= Html::encode($bank->comment); ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($model->companies || $model->users) : ?>
        <section class="sx-contractor-section">
            <h3 class="sx-contractor-section-title">Связи</h3>
            <div class="sx-contractor-grid is-two-columns">
                <?php foreach ($model->companies as $company) : ?>
                    <?= $entityCard(
                        '/cms/admin-cms-company',
                        $company,
                        'Компания',
                        $company->asText,
                        '',
                        'fa fa-building'
                    ); ?>
                <?php endforeach; ?>
                <?php foreach ($model->users as $user) : ?>
                    <?= $entityCard(
                        '/cms/admin-user',
                        $user,
                        'Контакт',
                        $user->shortDisplayName,
                        $user->email ?: $user->phone,
                        'fa fa-user'
                    ); ?>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($hasPrintAssets) : ?>
        <section class="sx-contractor-section">
            <h3 class="sx-contractor-section-title">Материалы для документов</h3>
            <div class="sx-contractor-grid">
                <?php foreach ([
                    'Логотип' => $model->cmsImage,
                    'Печать' => $model->stamp,
                    'Подпись руководителя' => $model->directorSignature,
                    'Подпись бухгалтера' => $model->signatureAccountant,
                ] as $label => $file) : ?>
                    <?php if ($file) : ?>
                        <div class="sx-contractor-media">
                            <div class="sx-contractor-label"><?= Html::encode($label); ?></div>
                            <div class="sx-contractor-media-preview">
                                <a href="<?= Html::encode($file->src); ?>" target="_blank" data-pjax="0">
                                    <img src="<?= Html::encode($file->src); ?>" alt="<?= Html::encode($label); ?>">
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if (trim((string)$model->description) !== '') : ?>
        <section class="sx-contractor-section">
            <h3 class="sx-contractor-section-title">Комментарий</h3>
            <p class="sx-contractor-description"><?= Html::encode($model->description); ?></p>
        </section>
    <?php endif; ?>
</div>
