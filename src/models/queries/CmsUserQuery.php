<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\models\queries;

use skeeks\cms\query\CmsActiveQuery;
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class CmsUserQuery extends CmsActiveQuery
{
    /**
     * @param string $username
     * @return $this
     */
    public function username(string $username)
    {
        return $this->andWhere([$this->getPrimaryTableName().'.username' => $username]);
    }

    /**
     * @param string $username
     * @return $this
     */
    public function email(string $email)
    {
        $this->joinWith("cmsUserEmails as cmsUserEmails");
        $this->groupBy($this->getPrimaryTableName().'.id');

        return $this->andWhere(['cmsUserEmails.value' => trim($email)]);
    }

    /**
     * @param string $username
     * @return $this
     */
    public function phone(string $phone)
    {
        $this->joinWith("cmsUserPhones as cmsUserPhones");
        $this->groupBy($this->getPrimaryTableName().'.id');

        return $this->andWhere(['cmsUserPhones.value' => $phone]);
    }
}