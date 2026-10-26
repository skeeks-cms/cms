<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\rbac;

use skeeks\cms\deal\DealAccess;
use yii\rbac\Rule;

/**
 * RBAC entry to the deal access service for client people:
 *
 * ```php
 * \Yii::$app->user->can(CmsDealAreaRule::PERMISSION, ['deal' => $deal, 'area' => 'billing']);
 * \Yii::$app->user->can(CmsDealAreaRule::PERMISSION, ['deal' => $deal, 'area' => 'tasks', 'level' => DealAccess::LEVEL_FULL]);
 * ```
 *
 * The data lives in cms_client_access; RBAC only gives the standard can()
 * and AccessControl entry point.
 */
class CmsDealAreaRule extends Rule
{
    const NAME = 'CmsDealAreaRule';
    const PERMISSION = 'cms/deal-area';

    public $name = self::NAME;

    public function execute($user, $item, $params)
    {
        if (empty($params['deal']) || empty($params['area']) || !\Yii::$app->has('dealAccess')) {
            return false;
        }

        /** @var DealAccess $dealAccess */
        $dealAccess = \Yii::$app->get('dealAccess');
        return $dealAccess->can(
            (int)$user,
            $params['deal'],
            (string)$params['area'],
            (int)($params['level'] ?? DealAccess::LEVEL_VIEW)
        );
    }
}
