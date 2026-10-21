<?php

namespace skeeks\cms\widgets\admin;

use yii\base\Event;

/**
 * Actions of one phone or email row in a CRM card, see
 * {@see CrmContactBlockWidget::contactActions()}.
 *
 * Handlers add, replace or remove entries of `$actions` (id => trusted HTML,
 * rendered in order). The card supplies its default actions, for example
 * `sms` and `call` for a phone or `mailto` for an email. A handler checks its
 * own permissions: the defaults may be empty when the employee cannot work
 * with the record.
 */
class CrmContactActionsEvent extends Event
{
    /** @var string CrmContactBlockWidget::CONTACT_PHONE or CONTACT_EMAIL */
    public string $contactType = '';

    /** @var string the phone or email value as stored */
    public string $value = '';

    /** @var \yii\base\Model|null the card record: CmsLead, CmsCompany, CmsUser */
    public $owner;

    /** @var string[] id => trusted HTML */
    public array $actions = [];
}
