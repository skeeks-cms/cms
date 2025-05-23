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
use skeeks\cms\models\CmsTask;
use skeeks\cms\models\CmsUser;
use skeeks\cms\rbac\Item;
use yii\rbac\Rule;

/**
 * Checks if authorID matches user passed via params
 */
class CmsTaskRule extends Rule
{
    const NAME = 'CmsTaskRule';

    public $name = self::NAME;

    /**
     * @param string|integer $user the user ID.
     * @param Item $item the role or permission that this rule is associated with
     * @param array $params parameters passed to ManagerInterface::checkAccess().
     * @return boolean a value indicating whether the rule permits the role or permission it is associated with.
     */
    public function execute($user, $item, $params)
    {
        if (isset($params['model'])) {
            /**
             * Пользователь доступ к которому проверяем
             * @var CmsUser $currentUser
             * @var CmsUser $model
             */
            $model = $params['model'];
            $currentUser = CmsUser::findOne($user);

            //Если пользователь является подчиненным для текущего, то его можно редактировать
            /*if ($currentUser->getSubordinates()->andWhere(['id' => $model->id])->exists()) {
                return true;
            }*/

            //Если пользователь доступен для текущего
            if (CmsTask::find()
                ->forManager($currentUser) //Поиск пользователей доступных текущему пользователю
                ->andWhere([CmsTask::tableName() . '.id' => $model->id]) //Пользователь которого проверяем
                ->exists() ) {
                return true;
            }
        }

        return false;
    }
}