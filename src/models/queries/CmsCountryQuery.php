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
class CmsCountryQuery extends CmsActiveQuery
{
    /**
     * @param string $alpha2
     * @return CmsCountryQuery
     */
    public function alpha2(string $alpha2)
    {
        return $this->andWhere(['alpha2' => strtoupper($alpha2)]);
    }

    /**
     * @param string $alpha3
     * @return CmsCountryQuery
     */
    public function alpha3(string $alpha3)
    {
        return $this->andWhere(['alpha3' => strtoupper($alpha3)]);
    }

    /**
     * @param string $alpha3
     * @return CmsCountryQuery
     */
    public function iso(string $iso)
    {
        return $this->andWhere(['iso' => $iso]);
    }


    /**
     * @param mixed $phone_code
     * @return CmsCountryQuery
     */
    public function phoneCode(mixed $phone_code)
    {
        $phone_code = (string) $phone_code;
        if (strpos($phone_code, "+") === false) {
            $phone_code = "+" . $phone_code;
        }
        return $this->andWhere(['phone_code' => $phone_code]);
    }

}