<?php
/**
 * AuthorRule
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 21.11.2014
 * @since 1.0.0
 */

namespace skeeks\cms\rbac;

use skeeks\cms\models\CmsLog;
use skeeks\cms\rbac\Item;
use yii\rbac\Rule;

/**
 * Checks if authorID matches user passed via params
 */
class CmsLogRule extends Rule
{
    const NAME = 'cmsLogRule';

    public $name = self::NAME;

    /**
     * @param string|integer $user the user ID.
     * @param Item $item the role or permission that this rule is associated with
     * @param array $params parameters passed to ManagerInterface::checkAccess().
     * @return boolean a value indicating whether the rule permits the role or permission it is associated with.
     */
    public function execute($user, $item, $params)
    {
        if (isset($params['model']) && isset($params['model']->created_by)) {
            /**
             * @var CmsLog $model
             */
            $model = $params['model'];
            //Если пользователь является автором комментария
            if ($model->created_by == $user) {
                //Только комментарий
                if ($model->log_type == CmsLog::LOG_TYPE_COMMENT) {
                    //Если с момента написания комментария прошло менее 24 часов
                    if (time() - $params['model']->created_at < 60*60*24) {
                        return true;
                    }
                }

            }
        }

        return false;
    }
}