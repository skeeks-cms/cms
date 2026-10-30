<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\models\queries;

use skeeks\cms\models\CmsCompany;
use skeeks\cms\models\CmsContractor;
use skeeks\cms\models\CmsLog;
use skeeks\cms\models\CmsUser;
use skeeks\cms\query\CmsActiveQuery;
use skeeks\cms\rbac\CmsManager;
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class CmsLogQuery extends CmsActiveQuery
{
    /** Above this many records of one kind a feed keeps the subquery instead of a list. */
    public const FEED_IDS_LIMIT = 2000;

    /**
     * The ids of a client's records of one kind (its deals, bills, tasks…) for
     * a feed condition `model_id IN (…)`: an explicit list, not a subquery.
     * With subqueries in a chain of OR MariaDB merged the single-column
     * indexes over ~70 000 entries and sorted them (audit final4: a company
     * feed 150–740 ms); with lists it reads the (model_code, model_id) index,
     * a few ms. A kind with more than FEED_IDS_LIMIT records stays a subquery.
     *
     * @param \yii\db\ActiveQuery $records a relation query of the client
     * @return int[]|\yii\db\ActiveQuery
     */
    public static function feedIds(\yii\db\ActiveQuery $records)
    {
        $ids = (clone $records)->limit(self::FEED_IDS_LIMIT + 1)->column();
        if (count($ids) > self::FEED_IDS_LIMIT) {
            return $records;
        }

        // An empty list is "0=1" in the condition.
        return array_map('intval', $ids);
    }

    /**
     * The records of the deals (CmsLead) of a company or a client person for
     * its card feed, as in Bitrix24. A letter, a call, a proposal event or a
     * visit written to the client and to its deal is shown once: the deal
     * record is skipped when the client has the same record — the same
     * `data`, or for letters and calls the same `data.id` (a call on the
     * company keeps more fields). Who sees which deals is
     * crm\CrmAccess::feedCondition() of the card.
     *
     * @param CmsCompany|\skeeks\cms\models\User $record
     */
    public static function clientDealsCondition($record): array
    {
        $table = CmsLog::tableName();
        // An explicit list (a client has few deals): the (model_code, model_id) index is used, not a scan by model_code.
        $leads = array_map('intval', \skeeks\cms\models\CmsLead::find()->select('id')
            ->andWhere([$record instanceof CmsCompany ? 'cms_company_id' : 'cms_user_id' => (int)$record->id])->column());
        if (!$leads) {
            return ['and', '0=1'];
        }
        // A limited feed («Свои сделки», "edit": only the records of his deals, crm\CrmAccess::feedCondition())
        // never shows the client's copy of a letter or a call: the copy of the deal must stay, no twin is dropped.
        if (\Yii::$app->has('user') && \Yii::$app->user instanceof \yii\web\User && \Yii::$app->user->identity
            && \skeeks\cms\crm\CrmAccess::instance()->feedCondition(\Yii::$app->user->identity, $record) !== null) {
            return ['and', [$table.'.model_code' => (new \skeeks\cms\models\CmsLead())->skeeksModelCode], [$table.'.model_id' => $leads]];
        }
        $twinId = static fn(string $alias) => "JSON_UNQUOTE(JSON_EXTRACT($alias.data, '$.id'))";
        $twin = (new \yii\db\Query())->select(new \yii\db\Expression('1'))->from(['twin' => $table])
            ->andWhere(['twin.model_code' => $record->skeeksModelCode, 'twin.model_id' => (int)$record->id])
            ->andWhere('twin.log_type = '.$table.'.log_type')
            // Only the events written to both (comments and changes are never twins; their data is often "[]").
            ->andWhere([$table.'.log_type' => [CmsLog::LOG_TYPE_PHONE_CALL, 'message', 'proposal', 'comm_visit']])
            ->andWhere(['or',
                'twin.data = '.$table.'.data',
                ['and', [$table.'.log_type' => [CmsLog::LOG_TYPE_PHONE_CALL, 'message']], $twinId('twin').' = '.$twinId($table)],
            ]);

        return ['and',
            [$table.'.model_code' => (new \skeeks\cms\models\CmsLead())->skeeksModelCode],
            [$table.'.model_id' => $leads],
            ['not exists', $twin],
        ];
    }

    /**
     * Not pinned records of the card itself: the records of its deals stay in
     * the feed even when pinned in their deal (pinned ones of the card are
     * shown above it).
     *
     * @param CmsCompany|\skeeks\cms\models\User $record
     */
    public function notPinnedOf($record)
    {
        return $this->andWhere(['or', ['is_pinned' => 0], ['is_pinned' => null], ['<>', CmsLog::tableName().'.model_code', $record->skeeksModelCode]]);
    }

    /**
     * @param string|array $types
     * @return $this
     */
    public function logType($types)
    {
        $this->andWhere(['log_type' => $types]);
        return $this;
    }
    
    /**
     * @return $this
     */
    public function comments()
    {
        return $this->logType(CmsLog::LOG_TYPE_COMMENT);
    }

    /**
     * @return $this
     */
    public function pinned()
    {
        return $this->andWhere(['is_pinned' => 1]);
    }

    /**
     * Entries that are not pinned: a feed shows pinned entries once, on top.
     *
     * @return $this
     */
    public function notPinned()
    {
        return $this->andWhere(['or', ['is_pinned' => 0], ['is_pinned' => null]]);
    }

    /**
     * @return $this
     */
    public function results()
    {
        return $this->pinned();
    }

    /**
     * @return $this
     */
    public function phoneCalls()
    {
        return $this->logType(CmsLog::LOG_TYPE_PHONE_CALL);
    }

    /**
     * Entries of the activity feed of CRM cards: calls, comments and the types
     * registered by packages in Skeeks::$activityLogTypes.
     *
     * @return $this
     */
    public function activity()
    {
        return $this->logType(\Yii::$app->skeeks->activityLogTypes);
    }


}
