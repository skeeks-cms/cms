<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\models\queries;

use skeeks\cms\models\CmsContractor;
use skeeks\cms\query\CmsActiveQuery;
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class CmsContractorQuery extends CmsActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * {@inheritdoc}
     * @return CmsContractor[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return CmsContractor|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }

    /**
     * @param $type
     * @return CmsContractorQuery
     */
    public function type($type)
    {
        return $this->andWhere(['contractor_type' => $type]);
    }
    /**
     * @return CmsContractorQuery
     */
    public function typeLegal()
    {
        return $this->type(CmsContractor::TYPE_LEGAL);
    }

    /**
     * @return CmsContractorQuery
     */
    public function typeIndividual()
    {
        return $this->type(CmsContractor::TYPE_INDIVIDUAL);
    }


    /**
     * @return CmsContractorQuery
     */
    public function typeIndividualAndLegal()
    {
        return $this->type([
            CmsContractor::TYPE_INDIVIDUAL,
            CmsContractor::TYPE_LEGAL,
        ]);
    }

    /**
     * @param string $q
     * @return CmsContractorQuery
     */
    public function search($q = '')
    {
        return $this->andWhere([
            'or',
            ['like', 'first_name', $q],
            ['like', 'last_name', $q],
            ['like', 'email', $q],
            ['like', 'phone', $q],
            ['like', 'name', $q],
            ['like', 'inn', $q],
        ]);
    }

    /**
     * @param string $inn
     * @return CmsContractorQuery
     */
    public function inn($inn)
    {
        return $this->andWhere([
            'inn' => $inn
        ]);
    }
    
    
    /**
     * @param $value
     * @return CrmContractorQuery
     */
    public function our($value = 1)
    {
        return $this->andWhere(['is_our' => (int) $value]);
    }


}