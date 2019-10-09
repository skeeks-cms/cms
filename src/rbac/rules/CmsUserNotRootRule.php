<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\rbac\rules;

use skeeks\cms\models\CmsUser;
use skeeks\crm\models\CrmProject;
use yii\rbac\Rule;

/**
 * Проверка что пользователь участник проекта
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class CmsUserNotRootRule extends Rule
{
    const NAME = 'CmsUserNotRootRule';

    public $name = self::NAME;

    /**
     * @param string|integer $user the user ID.
     * @param Item           $item the role or permission that this rule is associated with
     * @param array          $params parameters passed to ManagerInterface::checkAccess().
     * @return boolean a value indicating whether the rule permits the role or permission it is associated with.
     */
    public function execute($user, $item, $params)
    {

        if (isset($params['model'])) {

            $model = $params['model'];

            if ($model instanceof CmsUser) {

                if ($model) {

                    //Если я ведущий проекта
                    print_r($model->roles);die;
                }
            }
        }

        return false;
    }
}