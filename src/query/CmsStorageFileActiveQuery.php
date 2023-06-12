<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\query;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class CmsStorageFileActiveQuery extends CmsActiveQuery
{
    /**
     * @param string $clusterId
     * @param string $clusterFile
     * @return CmsStorageFileActiveQuery
     */
    public function clusterFile(string $clusterId, string $clusterFile)
    {
        return $this
            ->andWhere([$this->getPrimaryTableName().'.cluster_id' => $clusterId])
            ->andWhere([$this->getPrimaryTableName().'.cluster_file' => $clusterFile])
        ;
    }
    
    /**
     * @param string $clusterId
     * @return CmsStorageFileActiveQuery
     */
    public function cluster(string $clusterId)
    {
        return $this
            ->andWhere([$this->getPrimaryTableName().'.cluster_id' => $clusterId])
        ;
    }
}