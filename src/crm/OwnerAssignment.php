<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\crm;

use skeeks\cms\models\CmsCompany;
use skeeks\cms\models\CmsCrmAccessRequest;
use skeeks\cms\models\CmsUser;

/**
 * The main responsible of the existing base by its old responsibles (the links
 * company2manager / user2manager made before the main responsible existed),
 * for a site moving to the new access model (owner's decision 30.10.2026,
 * skeeks.com: the companies of the owners stay theirs and «Всегда по
 * согласованию», the companies of a manager stay his).
 *
 * Every company and client person with links falls into one group:
 *
 * - owner: links to owners (--owners) and to nobody of keep/review — the main
 *   one is an owner (the current main one if an owner, else the author if an
 *   owner, else the earliest owner link), the protection becomes «always»;
 * - keep: links to exactly one employee of keep (--keep), to no owner or
 *   review — he becomes the main one, the protection is left as it is;
 * - contested, only counted and listed: owners together with keep employees
 *   (shared), several keep employees, any link to a review employee (--review);
 * - other: links only to other employees — left as they are;
 * - none: no links at all — counted (client persons only when they are CRM
 *   clients: not in a company with employees, created by someone else or with
 *   a history);
 * - contact: a client person without links of his own, a contact of companies
 *   whose main responsible (after this plan) is one and the same employee — he
 *   becomes the person's main one, so the personal orders of the person (seen
 *   only through the access to the person himself) stay with the responsible of
 *   his company; «always» when that employee is an owner (owner's decision
 *   30.10.2026). Companies of different mains or a main from review make the
 *   person contested (contact_contested), only listed.
 *
 * Other participants keep their links and levels. Each change is written to
 * the journal of the access («Доступы»); a repeated run changes nothing.
 */
class OwnerAssignment
{
    public const OWNER = 'owner';
    public const KEEP = 'keep';
    public const SHARED = 'shared';
    public const MANY_KEEP = 'many_keep';
    public const REVIEW = 'review';
    public const OTHER = 'other';
    public const NONE = 'none';
    public const CONTACT = 'contact';
    public const CONTACT_CONTESTED = 'contact_contested';

    /** @var int[] */
    private array $owners;
    /** @var int[] */
    private array $keep;
    /** @var int[] */
    private array $review;

    public function __construct(array $owners, array $keep = [], array $review = [])
    {
        $this->owners = array_values(array_unique(array_map('intval', $owners)));
        $this->keep = array_values(array_unique(array_map('intval', $keep)));
        $this->review = array_values(array_unique(array_map('intval', $review)));
    }

    public static function ready(): bool
    {
        return CrmAccess::mainReady() && CrmProtection::ready() && CrmAccessLog::ready();
    }

    /**
     * @return array[] code, id, name, group, users (linked ids), main (current), user_id (planned main or null),
     *                 protection (current), always (bool: becomes «always»), change (bool)
     */
    public function plan(): array
    {
        $plan = [];
        foreach ([CmsCrmAccessRequest::RECORD_COMPANY => CmsCompany::class, CmsCrmAccessRequest::RECORD_CLIENT => CmsUser::class] as $code => $class) {
            [$linkClass, $recordColumn, $userColumn] = CrmAccess::linkTable($code);
            $links = [];
            $mains = [];
            foreach ($linkClass::find()->select(['id', $recordColumn, $userColumn, 'is_main'])->orderBy(['id' => SORT_ASC])->asArray()->each(1000) as $row) {
                $links[(int)$row[$recordColumn]][] = (int)$row[$userColumn];
                if ((int)$row['is_main']) {
                    $mains[(int)$row[$recordColumn]] = (int)$row[$userColumn];
                }
            }
            $table = $class::tableName();
            $query = $class::find()->andWhere(['id' => array_keys($links)])->orderBy([$table.'.id' => SORT_ASC]);
            foreach ($query->each(500) as $record) {
                $plan[] = $this->row($code, $record, array_values(array_unique($links[(int)$record->id])), $mains[(int)$record->id] ?? null);
            }
        }

        return array_merge($plan, $this->contactRows($plan));
    }

    /**
     * Client persons without links of their own who are contacts of companies with a main
     * responsible (the planned one when the plan changes it).
     *
     * @param array[] $plan the rows of the linked records
     */
    private function contactRows(array $plan): array
    {
        $companyMain = [];
        foreach ($plan as $row) {
            if ($row['code'] === CmsCrmAccessRequest::RECORD_COMPANY && ($row['user_id'] ?? $row['main']) !== null) {
                $companyMain[$row['id']] = (int)($row['user_id'] ?? $row['main']);
            }
        }
        [$linkClass, $recordColumn] = CrmAccess::linkTable(CmsCrmAccessRequest::RECORD_CLIENT);
        $table = CmsUser::tableName();
        $companies = [];
        foreach ((new \yii\db\Query())->select(['person' => 'cu.cms_user_id', 'company' => 'cu.cms_company_id'])->from(['cu' => '{{%cms_company2user}}'])
                     ->innerJoin(['p' => $table], 'p.id = cu.cms_user_id')
                     ->andWhere(['p.is_worker' => 0])
                     ->andWhere(['not exists', $linkClass::find()->select('id')->andWhere([$recordColumn => new \yii\db\Expression('cu.cms_user_id')])])
                     ->orderBy(['cu.cms_user_id' => SORT_ASC, 'cu.cms_company_id' => SORT_ASC])->each(1000) as $link) {
            $companies[(int)$link['person']][] = (int)$link['company'];
        }
        $rows = [];
        foreach ($companies as $personId => $ids) {
            $mains = array_values(array_unique(array_filter(array_map(static fn($id) => $companyMain[$id] ?? null, $ids))));
            if (!$mains) {
                continue;
            }
            $person = CmsUser::findOne($personId);
            if (!$person) {
                continue;
            }
            $contested = count($mains) > 1 || array_intersect($mains, $this->review);
            $userId = $contested ? null : $mains[0];
            $always = $userId !== null && in_array($userId, $this->owners, true);
            $protection = $person->hasAttribute('crm_protection') ? (string)$person->crm_protection : null;
            $rows[] = [
                'code' => CmsCrmAccessRequest::RECORD_CLIENT,
                'id' => $personId,
                'name' => (string)$person->shortDisplayName,
                'group' => $contested ? self::CONTACT_CONTESTED : self::CONTACT,
                'users' => $mains,
                'companies' => $ids,
                'main' => null,
                'user_id' => $userId,
                'protection' => $protection,
                'always' => $always,
                'change' => $userId !== null,
            ];
        }

        return $rows;
    }

    /** Records without any responsible: companies, and client persons that are CRM clients. */
    public function withoutResponsible(): array
    {
        $count = [];
        foreach ([CmsCrmAccessRequest::RECORD_COMPANY => CmsCompany::class, CmsCrmAccessRequest::RECORD_CLIENT => CmsUser::class] as $code => $class) {
            [$linkClass, $recordColumn] = CrmAccess::linkTable($code);
            $table = $class::tableName();
            $query = $class::find()->andWhere(['not exists', $linkClass::find()->select('id')->andWhere([$recordColumn => new \yii\db\Expression($table.'.id')])]);
            if ($code === CmsCrmAccessRequest::RECORD_CLIENT) {
                $query->andWhere([$table.'.is_worker' => 0])
                    ->andWhere(['not exists', CrmAccess::personCompanyLinks($table.'.id')])
                    ->andWhere(['or',
                        ['and', ['not', [$table.'.created_by' => null]], ['<>', $table.'.created_by', new \yii\db\Expression($table.'.id')]],
                        CrmAccess::historyCondition($code, $table.'.id'),
                    ]);
            }
            $count[$code] = (int)$query->count();
        }

        return $count;
    }

    /** Writes the owner and keep rows of the plan; returns [mains set, protections set]. */
    public function apply(array $plan): array
    {
        $access = CrmAccess::instance();
        $protection = new CrmProtection();
        $mains = 0;
        $always = 0;
        foreach ($plan as $row) {
            if (!$row['change']) {
                continue;
            }
            $code = $row['code'];
            $id = (int)$row['id'];
            $userId = (int)$row['user_id'];
            if ($access->mainResponsibleId($code, $id) !== $userId) {
                $levelBefore = $access->linkLevel($userId, $code, $id);
                $access->setMain($code, $id, $userId);
                CrmAccessLog::write(CrmAccessLog::MAIN_AUTO, $code, $id, ['actor_id' => null, 'user_id' => $userId, 'level_from' => $levelBefore,
                    'level_to' => CrmAccess::LEVEL_FULL, 'comment' => $row['group'] === self::CONTACT
                        ? 'основной контакта — основной его компании, при переходе на новую модель доступа'
                        : 'основной по прежним ответственным при переходе на новую модель доступа']);
                $mains++;
            }
            if ($row['always']) {
                $class = $code === CmsCrmAccessRequest::RECORD_COMPANY ? CmsCompany::class : CmsUser::class;
                $before = (string)$class::find()->select('crm_protection')->andWhere(['id' => $id])->scalar();
                if ($before !== CrmProtection::ALWAYS) {
                    $protection->setAlways($code, $id, true);
                    CrmAccessLog::write(CrmAccessLog::PROTECTION, $code, $id, ['actor_id' => null,
                        'comment' => (CrmProtection::states()[$before] ?? $before).' → '.CrmProtection::states()[CrmProtection::ALWAYS]
                            .', клиент владельца при переходе на новую модель доступа']);
                    $always++;
                }
            }
        }
        $access->resetMemo();

        return [$mains, $always];
    }

    /**
     * Every client (company or person) whose main responsible is an owner and that is not
     * «Всегда по согласованию» yet (owner's decision 30.10.2026: all clients of the owners are
     * protected, including those given to them from the former pool).
     *
     * @return array[] code, id, name, user_id (the owner), protection (current)
     */
    public function protectionPlan(): array
    {
        $plan = [];
        foreach ([CmsCrmAccessRequest::RECORD_COMPANY => CmsCompany::class, CmsCrmAccessRequest::RECORD_CLIENT => CmsUser::class] as $code => $class) {
            [$linkClass, $recordColumn, $userColumn] = CrmAccess::linkTable($code);
            $mains = $linkClass::find()->select([$userColumn, $recordColumn])->andWhere(['is_main' => 1, $userColumn => $this->owners ?: [0]])
                ->asArray()->all();
            $owner = [];
            foreach ($mains as $row) {
                $owner[(int)$row[$recordColumn]] = (int)$row[$userColumn];
            }
            if (!$owner) {
                continue;
            }
            $table = $class::tableName();
            $query = $class::find()->andWhere([$table.'.id' => array_keys($owner)])
                ->andWhere(['or', [$table.'.crm_protection' => null], ['<>', $table.'.crm_protection', CrmProtection::ALWAYS]])
                ->orderBy([$table.'.id' => SORT_ASC]);
            foreach ($query->each(500) as $record) {
                $plan[] = [
                    'code' => $code,
                    'id' => (int)$record->id,
                    'name' => $record instanceof CmsCompany ? (string)$record->name : (string)$record->shortDisplayName,
                    'user_id' => $owner[(int)$record->id],
                    'protection' => (string)$record->crm_protection,
                ];
            }
        }

        return $plan;
    }

    /**
     * Contacts of owner companies still waiting for the main responsible of their company (plan()
     * rows of the group contact that make an owner the main one). A company given to an owner from
     * the former pool after the first assign-owners leaves its contacts here until assign-owners
     * runs again (release audit F4-01, 30.10.2026); protectionPlan() does not see them, as they
     * have no main yet.
     *
     * @return array[] the plan() rows
     */
    public function pendingOwnerContacts(): array
    {
        return array_values(array_filter($this->plan(), fn(array $row) => $row['group'] === self::CONTACT && $row['change']
            && in_array((int)$row['user_id'], $this->owners, true)));
    }

    /** Applies protectionPlan(): «Всегда по согласованию», each change in the journal; returns the count. */
    public function applyProtection(array $plan): int
    {
        $protection = new CrmProtection();
        $count = 0;
        foreach ($plan as $row) {
            $class = $row['code'] === CmsCrmAccessRequest::RECORD_COMPANY ? CmsCompany::class : CmsUser::class;
            $before = (string)$class::find()->select('crm_protection')->andWhere(['id' => (int)$row['id']])->scalar();
            if ($before === CrmProtection::ALWAYS) {
                continue;
            }
            $protection->setAlways($row['code'], (int)$row['id'], true);
            CrmAccessLog::write(CrmAccessLog::PROTECTION, $row['code'], (int)$row['id'], ['actor_id' => null, 'user_id' => (int)$row['user_id'],
                'comment' => (CrmProtection::states()[$before] ?? $before).' → '.CrmProtection::states()[CrmProtection::ALWAYS]
                    .', клиент владельца при переходе на новую модель доступа']);
            $count++;
        }

        return $count;
    }

    /** @param CmsCompany|CmsUser $record */
    private function row(string $code, $record, array $users, ?int $main): array
    {
        $owners = array_values(array_intersect($users, $this->owners));
        $keep = array_values(array_intersect($users, $this->keep));
        $review = array_values(array_intersect($users, $this->review));
        $userId = null;
        if ($review) {
            $group = self::REVIEW;
        } elseif ($owners && $keep) {
            $group = self::SHARED;
        } elseif ($owners) {
            $group = self::OWNER;
            $author = $record->hasAttribute('created_by') ? (int)$record->created_by : 0;
            $userId = $main !== null && in_array($main, $owners, true) ? $main : (in_array($author, $owners, true) ? $author : $owners[0]);
        } elseif (count($keep) > 1) {
            $group = self::MANY_KEEP;
        } elseif ($keep) {
            $group = self::KEEP;
            $userId = $keep[0];
        } else {
            $group = self::OTHER;
        }
        $protection = $record->hasAttribute('crm_protection') ? (string)$record->crm_protection : null;
        $always = $group === self::OWNER;

        return [
            'code' => $code,
            'id' => (int)$record->id,
            'name' => $record instanceof CmsCompany ? (string)$record->name : (string)$record->shortDisplayName,
            'group' => $group,
            'users' => $users,
            'main' => $main,
            'user_id' => $userId,
            'protection' => $protection,
            'always' => $always,
            'change' => $userId !== null && ($main !== $userId || ($always && $protection !== CrmProtection::ALWAYS)),
        ];
    }
}
