<?php

namespace skeeks\cms\crm;

use skeeks\cms\helpers\PhoneHelper;
use skeeks\cms\models\CmsCompany;
use skeeks\cms\models\CmsCompanyEmail;
use skeeks\cms\models\CmsCompanyPhone;
use skeeks\cms\models\CmsCrmAccessRequest;
use skeeks\cms\models\CmsCrmDuplicateMark;

/**
 * A strong match is never saved silently (П2а, owner's decision 28.10.2026):
 * a phone, an email or an INN of a company that another company already has
 * needs a reason — «Это другая компания» — and the pair is marked as a
 * possible duplicate (cms_crm_duplicate_mark). No unique index: one owner of
 * two legal entities may share a phone or an email legitimately.
 *
 * Used by the models of company phones and emails (every entry point — forms,
 * imports, API, sorting of deals — goes through their validation) and by
 * linking requisites with an INN to a company. Public mail domains are not
 * a match; the exact address is.
 */
class CrmDuplicateGuard
{
    /**
     * @param int|null $exceptRowId the row being saved: a moved row does not match its own old company
     * @return int[] other companies with this phone or email
     */
    public static function companiesWith(string $kind, string $value, ?int $exceptCompanyId = null, ?int $exceptRowId = null): array
    {
        $value = trim($value);
        if ($value === '') {
            return [];
        }
        if ($kind === 'phone') {
            $digits = PhoneHelper::searchDigits($value);
            $condition = PhoneHelper::equalCondition('value', $value);
            if (!$condition || strlen($digits) < 10) {
                return [];
            }
            $ids = [];
            $query = CmsCompanyPhone::find()->select(['cms_company_id', 'value'])->andWhere($condition);
            if ($exceptRowId) {
                $query->andWhere(['<>', 'id', $exceptRowId]);
            }
            foreach ($query->asArray()->all() as $row) {
                // LIKE '%digits' also matches longer numbers: the same significant digits only.
                if (PhoneHelper::searchDigits((string)$row['value']) === $digits) {
                    $ids[] = (int)$row['cms_company_id'];
                }
            }
        } else {
            $query = CmsCompanyEmail::find()->select('cms_company_id')->andWhere(['value' => mb_strtolower($value, 'UTF-8')]);
            if ($exceptRowId) {
                $query->andWhere(['<>', 'id', $exceptRowId]);
            }
            $ids = array_map('intval', $query->column());
        }
        $ids = array_values(array_unique($ids));

        return $exceptCompanyId ? array_values(array_diff($ids, [$exceptCompanyId])) : $ids;
    }

    /** "Этот телефон уже есть у компании «X» (ответственный …)". Company names are shown to anyone, as in a hidden match. */
    public static function message(string $what, array $companyIds): string
    {
        $access = CrmAccess::instance();
        $parts = [];
        foreach (CmsCompany::find()->andWhere(['id' => array_slice($companyIds, 0, 3)])->all() as $company) {
            $names = implode(', ', array_column($access->names($access->companyResponsibleIds((int)$company->id)), 'name'));
            $parts[] = '«'.$company->name.'»'.($names !== '' ? ' (ответственный '.$names.')' : '');
        }

        return $what.' уже есть у компании '.implode(', ', $parts)
            .'. Откройте существующую или, если это другая компания, напишите почему в поле «Почему это другая компания».';
    }

    /** The pair "new record — similar one" for an administrator; nothing is merged. */
    public static function mark(int $companyId, array $otherCompanyIds, string $reason): void
    {
        foreach (array_unique($otherCompanyIds) as $otherId) {
            $exists = CmsCrmDuplicateMark::find()->andWhere([
                'record_code' => CmsCrmAccessRequest::RECORD_COMPANY, 'record_id' => $companyId,
                'other_code' => CmsCrmAccessRequest::RECORD_COMPANY, 'other_id' => (int)$otherId,
            ])->exists();
            if (!$exists) {
                (new CmsCrmDuplicateMark([
                    'record_code' => CmsCrmAccessRequest::RECORD_COMPANY,
                    'record_id' => $companyId,
                    'other_code' => CmsCrmAccessRequest::RECORD_COMPANY,
                    'other_id' => (int)$otherId,
                    'reason' => mb_substr(trim($reason), 0, 2000),
                ]))->save(false);
            }
        }
    }

    /** @var string|null the mode for this request (tests switch it); null — CrmSettings of the site */
    public static ?string $mode = null;

    /** «Повторы клиентов» of the site: CrmSettings::DUPLICATES_STRICT (a site without the settings) or DUPLICATES_FREE. */
    public static function mode(): string
    {
        if (self::$mode !== null) {
            return self::$mode;
        }
        $settings = \Yii::$app->has('crmSettings') ? \Yii::$app->get('crmSettings') : null;

        return $settings instanceof CrmSettings && $settings->duplicateMode === CrmSettings::DUPLICATES_FREE
            ? CrmSettings::DUPLICATES_FREE
            : CrmSettings::DUPLICATES_STRICT;
    }

    public static function isStrict(): bool
    {
        return self::mode() === CrmSettings::DUPLICATES_STRICT;
    }

    /**
     * The one decision for every entry point (forms, tabs of phones and
     * emails, the deal form, imports, API): a strong match is refused only in
     * the strict mode and only without a reason. The pair is marked anyway.
     */
    public static function refuses(string $reason): bool
    {
        return self::isStrict() && trim($reason) === '';
    }

    /** The reason field stays in both modes; in the free one it is optional. */
    public static function reasonLabel(): string
    {
        return self::isStrict() ? 'Почему это другая компания' : 'Почему это другая компания (необязательно)';
    }

    /** The marks table exists (the migration may run after the code update). */
    public static function ready(): bool
    {
        static $ready = null;
        if ($ready === null) {
            $ready = CmsCompany::getDb()->getTableSchema(CmsCrmDuplicateMark::tableName(), true) !== null;
        }

        return $ready;
    }
}
