<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\models\queries;

use skeeks\cms\models\CmsCompany;
use skeeks\cms\models\CmsUser;
use skeeks\cms\models\User;
use skeeks\cms\query\CmsActiveQuery;
use skeeks\cms\rbac\CmsManager;
use yii\helpers\ArrayHelper;
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class CmsDealQuery extends CmsActiveQuery
{
    /**
     * Поиск компаний доступных пользователю
     *
     * @param User|null $user
     * @return $this
     */
    public function forManager(User $user = null)
    {
        if ($user === null) {
            $user = \Yii::$app->user->identity;
            $isCanAdmin = \Yii::$app->user->can(CmsManager::PERMISSION_ROLE_ADMIN_ACCESS);
        } else {
            $isCanAdmin = \Yii::$app->authManager->checkAccess($user->id, CmsManager::PERMISSION_ROLE_ADMIN_ACCESS);
        }

        if (!$user) {
            return $this;
        }


        //Если нет прав админа, нужно показать только доступные сделки
        if (!$isCanAdmin) {

            // A client seen in full, or "своё" for the level "Свои сделки" (CrmAccess::clientRecordCondition()).
            $access = \skeeks\cms\crm\CrmAccess::instance();
            $table = self::getPrimaryTableName();
            $own = [[$table.'.created_by' => $access->ownerIds($user)]];
            if (method_exists($access, 'ownLeadCondition')) {
                // A service of his deal (or of a subordinate's deal), owner's decision 29.10.2026.
                $own[] = $access->ownLeadCondition($user, $table.'.cms_lead_id', '{{%cms_deal}}');
            }
            $this->andWhere($access->clientRecordCondition($user, $table, $own));
        }

        return $this;
    }

    /**
     * Deals of a client person ("Сделка как услуга", DealAccess).
     *
     * With an access area — deals where the area is open and granted to the person
     * at least at $level; new screens always ask for their area.
     * Without an area — deals with **full** access only (owner, "полный доступ"),
     * the boundary every older screen was written for: a limited role (an
     * accountant, a marketer) never reaches screens that do not check an area.
     *
     * @param User|null   $user
     * @param string|null $area  DealAccess::AREA_* or an area of a package
     * @param int         $level DealAccess::LEVEL_*
     * @return $this
     */
    public function forClient(User $user = null, ?string $area = null, int $level = \skeeks\cms\deal\DealAccess::LEVEL_VIEW)
    {
        if ($user === null) {
            $user = \Yii::$app->user->identity;
        }

        if (!$user) {
            return $this;
        }

        if (\Yii::$app->has('dealAccess') && static::dealAccessReady()) {
            /** @var \skeeks\cms\deal\DealAccess $dealAccess */
            $dealAccess = \Yii::$app->get('dealAccess');
            $this->andWhere($area === null
                ? $dealAccess->fullCondition($user, $this->getPrimaryTableName())
                : $dealAccess->dealCondition($user, $area, $level, $this->getPrimaryTableName()));
            return $this;
        }

        //Before the migration of "Сделка как услуга": the previous rule.
        $cmsCompanyQuery = CmsCompany::find()->forClient($user)->select(CmsCompany::tableName() . '.id');

        //Поиск клиентов с которыми связан сотрудник + все дочерние сотрудники
        $this->andWhere([
            'or',
            //Связь клиентов с менеджерами
            [$this->getPrimaryTableName().".cms_company_id" => $cmsCompanyQuery],
            //Искать конткты по всем доступным компаниям
            [$this->getPrimaryTableName().".cms_user_id" => $user->id],
        ]);

        return $this;
    }

    /** @var bool|null */
    private static $_dealAccessReady;

    /**
     * The tables of "Сделка как услуга" exist (code may be updated before the migration).
     */
    public static function dealAccessReady(): bool
    {
        if (self::$_dealAccessReady === null) {
            self::$_dealAccessReady = (bool)\Yii::$app->db->getTableSchema('{{%cms_client_access}}');
        }
        return self::$_dealAccessReady;
    }
}