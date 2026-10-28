<?php
/**
 * «+ Добавить сотрудника»: opens the small form of the «crm-access-add»
 * action in the standard backend window (as CrmContactBlockWidget::addButton(),
 * plus enableNoModelActions(): the window is an action of the card's own
 * record, its header and tabs are not repeated); after the save the card
 * reloads. The same button in «Доступ» and in the card block «Работают с …».
 *
 * @var yii\web\View $this
 * @var string $controllerRoute e.g. "/cms/admin-cms-company"
 * @var int $recordId
 */

use skeeks\cms\backend\helpers\BackendUrlHelper;
use skeeks\cms\widgets\admin\CrmContactBlockWidget;
use yii\helpers\Html;
use yii\helpers\Json;

$action = [
    'isOpenNewWindow' => true,
    'size' => 'small',
    'url' => (string)BackendUrlHelper::createByParams([$controllerRoute.'/crm-access-add', 'pk' => $recordId])
        ->enableEmptyLayout()->enableNoActions()->enableNoModelActions()->url,
];

echo Html::a(CrmContactBlockWidget::icon('plus', 16).' '.Html::encode('Добавить сотрудника'), '#', [
    'class' => 'sx-contact-add',
    'data-pjax' => '0',
    'onclick' => 'new sx.classes.backend.widgets.Action('.Json::encode($action).').go(); return false;',
]);
