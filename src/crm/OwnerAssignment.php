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
 *   a history).
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

        return $plan;
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
                    'level_to' => CrmAccess::LEVEL_FULL, 'comment' => 'основной по прежним ответственным при переходе на новую модель доступа']);
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
