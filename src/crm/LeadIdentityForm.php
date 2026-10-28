<?php

namespace skeeks\cms\crm;

use yii\base\Model;

/**
 * "Кто это": the client of a deal chosen in the deal form or while sorting
 * an unsorted deal. Find or create a company and a contact person, or take
 * a deal without a client; a hidden match is sent back as a signed
 * reference (CrmAccess::candidateRef()), never as an id the employee chose.
 *
 * Applied by LeadTriage::applyIdentity() in one transaction with the deal.
 */
class LeadIdentityForm extends Model
{
    public const MODE_NONE = '';
    public const MODE_EXISTING = 'existing';
    public const MODE_NEW = 'new';
    /** A client of another responsible, found by the duplicate check. */
    public const MODE_FOREIGN = 'foreign';

    /**
     * «Работать с этим клиентом» (owner's decision 28.10.2026): the deal is the
     * actor's with the level "Свои сделки", the main responsible is notified.
     */
    public const FOREIGN_WORK = 'work';

    public $company_mode = self::MODE_NONE;
    public $company_id;
    public $company_ref;
    public $company_name;
    /** Phones and emails of a new company, comma separated (a parsed line, CrmContactParser). */
    public $company_phones;
    public $company_emails;

    public $client_mode = self::MODE_NONE;
    public $client_id;
    public $client_ref;
    public $client_first_name;
    public $client_last_name;
    public $client_phone;
    public $client_email;

    public $foreign_action = self::FOREIGN_WORK;
    public $foreign_comment;

    /** "Это другая компания": the similar record (signed ref) and why it is another one. */
    public $duplicate_ref;
    public $duplicate_reason;

    /** Responsible of the deal; empty — by the default rule. */
    public $executor_id;

    /**
     * Contacts of the request moved to the client card by choice:
     * "phone:<id>" | "email:<id>" => company | client | '' (stay with the deal only).
     */
    public $contacts = [];

    public function rules()
    {
        return [
            [['company_mode', 'client_mode'], 'in', 'range' => [self::MODE_NONE, self::MODE_EXISTING, self::MODE_NEW, self::MODE_FOREIGN]],
            [['foreign_action'], 'in', 'range' => [self::FOREIGN_WORK]],
            [['company_id', 'client_id', 'executor_id'], 'integer'],
            [['company_ref', 'client_ref', 'duplicate_ref'], 'string', 'max' => 512],
            [['company_name', 'client_first_name', 'client_last_name'], 'trim'],
            [['company_name', 'client_first_name', 'client_last_name'], 'string', 'max' => 255],
            [['client_phone', 'client_email', 'company_phones', 'company_emails'], 'trim'],
            [['company_phones', 'company_emails'], 'string', 'max' => 2000],
            [['client_phone'], 'string', 'max' => 64],
            [['client_email'], 'email'],
            [['foreign_comment', 'duplicate_reason'], 'string', 'max' => 2000],
            [['contacts'], 'safe'],
            [['company_id'], 'required', 'when' => fn() => $this->company_mode === self::MODE_EXISTING, 'message' => 'Выберите компанию.'],
            [['company_name'], 'required', 'when' => fn() => $this->company_mode === self::MODE_NEW, 'message' => 'Укажите название компании.'],
            [['company_ref'], 'required', 'when' => fn() => $this->company_mode === self::MODE_FOREIGN],
            [['client_id'], 'required', 'when' => fn() => $this->client_mode === self::MODE_EXISTING, 'message' => 'Выберите контакт.'],
            [['client_ref'], 'required', 'when' => fn() => $this->client_mode === self::MODE_FOREIGN],
            [['client_first_name'], 'required', 'when' => fn() => $this->client_mode === self::MODE_NEW, 'message' => 'Укажите имя контакта.'],
            // «Повторы клиентов»: the reason is required only in the strict mode (CrmDuplicateGuard).
            [['duplicate_reason'], 'required', 'when' => fn() => trim((string)$this->duplicate_ref) !== '' && CrmDuplicateGuard::isStrict(),
                'message' => 'Напишите, почему это другая компания.'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'company_mode' => 'Компания',
            'company_id' => 'Компания',
            'company_name' => 'Название компании',
            'company_phones' => 'Телефоны компании',
            'company_emails' => 'Email компании',
            'client_mode' => 'Контакт',
            'client_id' => 'Контакт',
            'client_first_name' => 'Имя',
            'client_last_name' => 'Фамилия',
            'client_phone' => 'Телефон',
            'client_email' => 'Email',
            'foreign_action' => 'Что сделать',
            'foreign_comment' => 'Комментарий для основного ответственного',
            'duplicate_reason' => CrmDuplicateGuard::reasonLabel(),
            'executor_id' => 'Ответственный',
        ];
    }

    /** @return string[] phones or emails of a new company from their comma separated field */
    public static function splitList($value): array
    {
        return array_values(array_unique(array_filter(array_map('trim', preg_split('/[,;\n]+/u', (string)$value) ?: []), 'strlen')));
    }

    public function isForeign(): bool
    {
        return $this->company_mode === self::MODE_FOREIGN || $this->client_mode === self::MODE_FOREIGN;
    }

    public function isEmpty(): bool
    {
        return $this->company_mode === self::MODE_NONE && $this->client_mode === self::MODE_NONE;
    }
}
