<?php
/**
 * «Кто это» (П2а): one search field for the company and one for the contact
 * person, as in amoCRM and Bitrix24. While typing (or after pasting a whole
 * line «Ромашка, +7 …, info@…»), CrmMatcher finds matches across the base:
 * own records are chosen, a client of another employee offers a request,
 * the last row «+ создать новую …» makes a record whose phone and email are
 * checked against the base while typed (_identity-check, a popover).
 * Fields belong to LeadIdentityForm; lead-identity.js fills the hidden ones.
 * Shared by the deal form, sorting of «Неразобранное» and «Клиент сделки».
 *
 * @var yii\web\View $this
 * @var skeeks\cms\crm\LeadIdentityForm $form
 * @var skeeks\cms\models\CmsLead $lead
 * @var array[] $candidates presented candidates for the initial hints (contacts of the request)
 * @var bool $allowForeign hidden matches may be chosen (a request for the deal or access)
 * @var bool $withExecutor show the responsible field (the deal form shows it last, _identity-executor)
 * @var bool $withContacts let the contacts of the request go to the client card
 */

use skeeks\cms\assets\admin\LeadIdentityAsset;
use skeeks\cms\crm\CrmAccess;
use skeeks\cms\crm\CrmDuplicateGuard;
use skeeks\cms\crm\LeadIdentityForm;
use skeeks\cms\models\CmsCompany;
use skeeks\cms\models\CmsUser;
use yii\helpers\Html;
use yii\helpers\Url;

LeadIdentityAsset::register($this);

$allowForeign = $allowForeign ?? true;
$withExecutor = $withExecutor ?? true;
$withContacts = $withContacts ?? false;
$hasCompany = (bool)$lead->cms_company_id;
$hasClient = (bool)$lead->cms_user_id;
$formName = $form->formName();
$hidden = static function (string $attribute, string $role) use ($form, $formName): string {
    return Html::hiddenInput($formName.'['.$attribute.']', (string)$form->{$attribute}, ['data-sx-identity-field' => $role]);
};
// The title of a choice that comes back after a validation error.
$pickedTitle = static function (string $part) use ($form): string {
    $mode = (string)$form->{$part.'_mode'};
    if ($mode === LeadIdentityForm::MODE_FOREIGN) {
        return 'Клиент другого сотрудника';
    }
    if ($mode === LeadIdentityForm::MODE_NEW) {
        return $part === 'company' ? (string)$form->company_name : trim($form->client_first_name.' '.$form->client_last_name);
    }
    if ($mode === LeadIdentityForm::MODE_EXISTING) {
        $access = CrmAccess::instance();
        $userId = (int)\Yii::$app->user->id;
        if ($part === 'company' && $access->canSeeCompany($userId, (int)$form->company_id) && ($company = CmsCompany::findOne((int)$form->company_id))) {
            return (string)$company->name;
        }
        if ($part === 'client' && $access->canSeeClient($userId, (int)$form->client_id) && ($client = CmsUser::findOne((int)$form->client_id))) {
            return (string)$client->shortDisplayName;
        }
    }

    return '';
};
/*
 * One field per part, as in Bitrix24: typing shows found records and, last,
 * «+ создать новую …»; a new record keeps the field as its name and opens
 * its phone and email, checked against the base while they are typed.
 */
$searchPart = static function (string $part, string $title, string $placeholder) use ($hidden, $form, $formName, $pickedTitle): string {
    $isCompany = $part === 'company';
    $fields = $isCompany
        ? ['company_mode' => 'mode', 'company_id' => 'id', 'company_ref' => 'ref', 'company_name' => 'name', 'company_phones' => 'phones', 'company_emails' => 'emails', 'company_site' => 'site']
        : ['client_mode' => 'mode', 'client_id' => 'id', 'client_ref' => 'ref', 'client_first_name' => 'first_name', 'client_last_name' => 'last_name', 'client_phone' => 'phone', 'client_email' => 'email'];
    $html = Html::tag('label', Html::encode($title), ['class' => 'sx-lead-identity__label', 'for' => 'sx-lead-identity-'.$part]);
    foreach ($fields as $attribute => $role) {
        $html .= $hidden($attribute, $role);
    }
    $html .= Html::tag('div',
        Html::tag('span', Html::tag('i', '', ['class' => $isCompany ? 'fas fa-building' : 'fas fa-user']), ['class' => 'sx-lead-identity__icon', 'aria-hidden' => 'true'])
        .Html::textInput('', $pickedTitle($part), [
            'id' => 'sx-lead-identity-'.$part,
            'class' => 'form-control sx-lead-identity__input',
            'placeholder' => $placeholder,
            'autocomplete' => 'off',
            'data-sx-identity-search' => $part,
            'data-placeholder' => $placeholder,
            'role' => 'combobox',
            'aria-expanded' => 'false',
        ])
        .Html::tag('span', '', ['class' => 'sx-lead-identity__badge', 'data-sx-identity-badge' => true, 'hidden' => true])
        .Html::button(Html::tag('i', '', ['class' => 'fas fa-exchange-alt']), [
            'class' => 'sx-lead-identity__switch',
            'title' => $isCompany ? 'Сменить или выбрать другую' : 'Сменить или выбрать другой',
            'aria-label' => $isCompany ? 'Сменить или выбрать другую' : 'Сменить или выбрать другой',
            'data-sx-identity-clear' => true,
            'hidden' => true,
        ])
        .Html::tag('div', '', ['class' => 'sx-lead-identity__results', 'data-sx-identity-results' => true, 'hidden' => true, 'aria-live' => 'polite']),
        ['class' => 'sx-lead-identity__box', 'data-sx-identity-box' => true]
    );
    // Phone and email of a new record: the hidden fields are filled from them.
    // П2д: a company found by a manager often starts from its site.
    $newFields = $isCompany ? ['phones' => 'Телефон', 'emails' => 'E-mail', 'site' => 'Сайт'] : ['phone' => 'Телефон', 'email' => 'E-mail'];
    $placeholders = ['phones' => '+7', 'phone' => '+7', 'site' => 'example.ru'];
    $inner = '';
    foreach ($newFields as $role => $label) {
        $value = (string)$form->{($isCompany ? 'company_' : 'client_').$role};
        $inner .= Html::tag('label',
            Html::tag('span', $label, ['class' => 'sx-lead-identity__label'])
            .Html::input($role === 'phones' || $role === 'phone' ? 'tel' : 'text', '', $value, [
                'class' => 'form-control',
                'autocomplete' => 'off',
                'placeholder' => $placeholders[$role] ?? 'E-mail',
                'data-sx-identity-new-field' => $role,
            ]),
            ['class' => 'sx-lead-identity__new-field']
        );
    }
    $inner .= Html::tag('div', '', ['class' => 'sx-lead-identity__check', 'data-sx-identity-check' => true, 'hidden' => true, 'aria-live' => 'polite']);
    $html .= Html::tag('div', $inner, ['class' => 'sx-lead-identity__new', 'data-sx-identity-new' => true, 'hidden' => true]);

    return Html::tag('section', $html, ['class' => 'sx-lead-identity__part', 'data-sx-identity-part' => $part]);
};
?>
<div class="sx-lead-identity" data-sx-lead-identity
     data-match-url="<?= Html::encode(Url::to(['/cms/admin-cms-lead/match'])); ?>"
     data-lead-id="<?= (int)$lead->id; ?>"
     data-allow-foreign="<?= $allowForeign ? 1 : 0; ?>"
     data-reason-required="<?= CrmDuplicateGuard::isStrict() ? 1 : 0; ?>">

    <div class="sx-lead-identity__parts">
        <?php if ($hasCompany) : ?>
            <section class="sx-lead-identity__part">
                <span class="sx-lead-identity__label">Компания</span>
                <p class="sx-lead-identity__fixed">Уже указана: <?= Html::encode((string)($lead->company ? $lead->company->name : '')); ?></p>
            </section>
        <?php else : ?>
            <?= $searchPart('company', 'Компания', 'Название, телефон, e-mail или ИНН'); ?>
        <?php endif; ?>

        <?php if ($hasClient) : ?>
            <section class="sx-lead-identity__part">
                <span class="sx-lead-identity__label">Контакт</span>
                <p class="sx-lead-identity__fixed">Уже указано: <?= Html::encode((string)($lead->client ? $lead->client->shortDisplayName : '')); ?></p>
            </section>
        <?php else : ?>
            <?= $searchPart('client', 'Контакт', 'Имя контакта, телефон или e-mail'); ?>
        <?php endif; ?>
    </div>

    <?php if ($allowForeign) : ?>
        <div class="sx-lead-identity__panel" data-sx-identity-foreign-panel hidden>
            <p class="sx-lead-identity__panel-title">Клиент закреплён за другим сотрудником. Сделка будет ваша, у вас появится доступ «Свои сделки» к клиенту, основному ответственному уйдёт уведомление — он может расширить ваш доступ.</p>
            <?= Html::hiddenInput($formName.'[foreign_action]', LeadIdentityForm::FOREIGN_WORK); ?>
            <?= Html::textarea($formName.'[foreign_comment]', $form->foreign_comment, ['class' => 'form-control', 'rows' => 2, 'placeholder' => 'Комментарий для основного ответственного (необязательно)']); ?>
        </div>
    <?php endif; ?>

    <div class="sx-lead-identity__panel" data-sx-identity-duplicate-panel hidden>
        <p class="sx-lead-identity__panel-title" data-sx-identity-duplicate-title></p>
        <?= Html::hiddenInput($formName.'[duplicate_ref]', $form->duplicate_ref, ['data-sx-identity-duplicate-ref' => true]); ?>
        <?= Html::textarea($formName.'[duplicate_reason]', $form->duplicate_reason, ['class' => 'form-control', 'rows' => 2,
            'placeholder' => 'Почему это другая запись: другой город, другой ИНН…'.(CrmDuplicateGuard::isStrict() ? '' : ' (необязательно)')]); ?>
    </div>

    <?php if ($candidates) : ?>
        <div class="sx-lead-identity__hints" data-sx-identity-hints>
            <?= $this->render('@skeeks/cms/views/admin-cms-lead/_identity-hints', ['candidates' => $candidates, 'live' => false]); ?>
        </div>
    <?php endif; ?>

    <?php if ($withContacts && ($lead->phones || $lead->emails)) : ?>
        <div class="sx-lead-identity__contacts">
            <h3 class="sx-lead-identity__title">Контакты из обращения</h3>
            <p class="sx-lead-identity__note">Они останутся в сделке. Отметьте, какие добавить в карточку клиента.</p>
            <?php foreach (array_merge(
                array_map(static fn($phone) => ['phone', $phone], $lead->phones),
                array_map(static fn($email) => ['email', $email], $lead->emails)
            ) as [$kind, $contact]) : ?>
                <label class="sx-lead-identity__contact">
                    <span><?= Html::encode((string)$contact->value); ?></span>
                    <?= Html::dropDownList($formName.'[contacts]['.$kind.':'.(int)$contact->id.']', '', [
                        '' => 'Только в сделке',
                        'client' => 'В карточку контакта',
                        'company' => 'В карточку компании',
                    ], ['class' => 'form-control']); ?>
                </label>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($withExecutor) : ?>
        <h3 class="sx-lead-identity__title">Ответственный</h3>
        <?= $this->render('@skeeks/cms/views/admin-cms-lead/_identity-executor', ['form' => $form]); ?>
    <?php endif; ?>
</div>
