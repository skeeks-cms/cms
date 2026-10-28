<?php
/**
 * «Клиент» of the deal card (П2г, owner's decision 28.10.2026): the contacts
 * live in the company or the person, the deal shows them with the same rows
 * and buttons as their cards; «Добавить …» writes into that card.
 *
 * @var $this yii\web\View
 * @var $model \skeeks\cms\models\CmsLead
 * @var $companyVisible bool
 * @var $clientVisible bool
 */

use skeeks\cms\backend\widgets\BackendSurfaceWidget;
use skeeks\cms\crm\CrmAccess;
use skeeks\cms\models\CmsCrmAccessRequest;
use skeeks\cms\widgets\admin\CrmContactBlockWidget;
use yii\helpers\Html;
use yii\helpers\Url;

$crmAccess = CrmAccess::instance();
$viewerId = (int)\Yii::$app->user->id;
$leadId = (int)$model->id;
$company = $model->company && $companyVisible ? $model->company : null;
$client = $model->client && $clientVisible ? $model->client : null;
// Rows of the deal card: the deal is the context of calls, letters and opt-out marks.
$rowOptions = static fn(bool $canEdit): array => ['editable' => $canEdit, 'call' => ['data-lead-id' => $leadId]];
$peopleLimit = 3;
$contactLimit = 5;

if ($company) {
    $canEdit = $crmAccess->canEditClientData($viewerId, CmsCrmAccessRequest::RECORD_COMPANY, (int)$company->id);
    $companyUrl = Url::to(['/cms/admin-cms-company/view', 'pk' => (int)$company->id]);

    // People of the company; the person of the deal first when the company does not have them.
    $people = [];
    foreach ($company->getCmsCompany2users()->with('cmsUser')->orderBy(['sort' => SORT_ASC])->all() as $link) {
        if ($link->cmsUser) {
            $people[(int)$link->cms_user_id] = ['user' => $link->cmsUser, 'note' => (string)$link->comment];
        }
    }
    if ($client && !isset($people[(int)$client->id])) {
        $people = [(int)$client->id => ['user' => $client, 'note' => 'Контакт сделки']] + $people;
    }

    $rows = '';
    foreach (array_slice($people, 0, $peopleLimit, true) as $person) {
        $user = $person['user'];
        $actions = ($user->phone ? CrmContactBlockWidget::contactActions(CrmContactBlockWidget::CONTACT_PHONE, (string)$user->phone, $model, [
                'call' => CrmContactBlockWidget::iconButton('phone', 'Позвонить', ['class' => 'sx-telephony-btn', 'data-value' => $user->phone, 'data-lead-id' => $leadId]),
            ]) : '')
            .($user->email ? CrmContactBlockWidget::contactActions(CrmContactBlockWidget::CONTACT_EMAIL, (string)$user->email, $model, [
                'mailto' => CrmContactBlockWidget::iconButton('mail', 'Написать письмо', ['href' => 'mailto:'.$user->email]),
            ]) : '');
        $rows .= Html::tag('div',
            Html::tag('div', \skeeks\cms\widgets\admin\CmsUserViewWidget::widget([
                'cmsUser' => $user,
                'append' => $person['note'] !== '' ? Html::tag('span', Html::encode($person['note']), ['class' => 'sx-contact-note']) : '',
            ]), ['class' => 'sx-contact-body'])
            .($actions !== '' ? Html::tag('div', $actions, ['class' => 'sx-contact-actions']) : ''),
            ['class' => 'sx-contact-row sx-lead-client-person']
        );
    }
    if (count($people) > $peopleLimit) {
        $rows .= CrmContactBlockWidget::moreLink(count($people) - $peopleLimit, $companyUrl);
    }
    if (!$people) {
        $rows .= Html::tag('div', 'Людей пока нет', ['class' => 'sx-contact-empty']);
    }

    BackendSurfaceWidget::begin([
        'title' => 'Люди компании',
        'titleTag' => 'h2',
        'raised' => true,
        'responsive' => true,
        'actions' => CrmContactBlockWidget::hintIcon('Люди компании «'.$company->name.'»: те же, что в её карточке. Новый человек добавляется в компанию.'),
    ]);
    echo Html::tag('div', $rows, ['class' => 'sx-contact-list']);
    if ($canEdit) {
        echo $this->render('@skeeks/cms/views/admin-cms-company/_add-person', [
            'model' => $company,
            'accessReady' => \skeeks\cms\models\queries\CmsDealQuery::dealAccessReady(),
        ]);
    }
    BackendSurfaceWidget::end();

    echo CrmContactBlockWidget::widget([
        'title' => 'Телефоны компании',
        'hint' => 'Телефоны из карточки компании. Новый телефон сохраняется в компанию.',
        'icon' => 'phone',
        'items' => CrmContactBlockWidget::phoneItems($company->phones, '/cms/admin-cms-company-phone', $model, $rowOptions($canEdit)),
        'limit' => $contactLimit,
        'moreUrl' => $companyUrl,
        'emptyText' => 'Телефонов пока нет',
        'addLabel' => 'Добавить телефон',
        'addRoute' => $canEdit ? ['/cms/admin-cms-company-phone/create', 'CmsCompanyPhone' => ['cms_company_id' => (int)$company->id]] : null,
        'addSize' => 'small',
    ]);

    echo CrmContactBlockWidget::widget([
        'title' => 'Email компании',
        'hint' => 'Адреса из карточки компании. Новый адрес сохраняется в компанию.',
        'icon' => 'mail',
        'items' => CrmContactBlockWidget::emailItems($company->emails, '/cms/admin-cms-company-email', $model, $rowOptions($canEdit)),
        'limit' => $contactLimit,
        'moreUrl' => $companyUrl,
        'emptyText' => 'Email пока нет',
        'addLabel' => 'Добавить email',
        'addRoute' => $canEdit ? ['/cms/admin-cms-company-email/create', 'CmsCompanyEmail' => ['cms_company_id' => (int)$company->id]] : null,
        'addSize' => 'small',
    ]);
} elseif ($client) {
    $canEdit = $crmAccess->canEditClientData($viewerId, CmsCrmAccessRequest::RECORD_CLIENT, (int)$client->id);
    $clientUrl = Url::to(['/cms/admin-user/view', 'pk' => (int)$client->id]);

    echo CrmContactBlockWidget::widget([
        'title' => 'Телефоны клиента',
        'hint' => 'Телефоны из карточки клиента. Новый телефон сохраняется в неё.',
        'icon' => 'phone',
        'items' => CrmContactBlockWidget::phoneItems($client->cmsUserPhones, '/cms/admin-user-phone', $model, $rowOptions($canEdit)),
        'limit' => $contactLimit,
        'moreUrl' => $clientUrl,
        'emptyText' => 'Телефонов пока нет',
        'addLabel' => 'Добавить телефон',
        'addRoute' => $canEdit ? ['/cms/admin-user-phone/create', 'CmsUserPhone' => ['cms_user_id' => (int)$client->id]] : null,
        'addSize' => 'small',
    ]);

    echo CrmContactBlockWidget::widget([
        'title' => 'Email клиента',
        'hint' => 'Адреса из карточки клиента. Новый адрес сохраняется в неё.',
        'icon' => 'mail',
        'items' => CrmContactBlockWidget::emailItems($client->cmsUserEmails, '/cms/admin-user-email', $model, $rowOptions($canEdit)),
        'limit' => $contactLimit,
        'moreUrl' => $clientUrl,
        'emptyText' => 'Email пока нет',
        'addLabel' => 'Добавить email',
        'addRoute' => $canEdit ? ['/cms/admin-user-email/create', 'CmsUserEmail' => ['cms_user_id' => (int)$client->id]] : null,
        'addSize' => 'small',
    ]);
}
