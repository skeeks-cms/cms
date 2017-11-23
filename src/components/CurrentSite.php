<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 27.03.2015
 */

namespace skeeks\cms\components;

use skeeks\cms\models\CmsSite;
use skeeks\cms\models\CmsSiteDomain;
use yii\base\Component;
use yii\caching\TagDependency;

/**
 * @property CmsSite $site
 * @package skeeks\cms\components
 */
class CurrentSite extends Component
{
    /**
     * @var CmsSite
     */
    protected $_site = null;

    private $_serverName = null;

    /**
     * @return CmsSite
     */
    public function getSite()
    {
        if ($this->_site === null) {
            if (\Yii::$app instanceof \yii\console\Application) {
                $this->_site = CmsSite::find()->active()->andWhere(['def' => Cms::BOOL_Y])->one();
            } else {
                $this->_serverName = \Yii::$app->getRequest()->getServerName();
                $dependencySiteDomain = new TagDependency([
                    'tags' =>
                        [
                            (new CmsSiteDomain())->getTableCacheTag(),
                        ],
                ]);


                $cmsDomain = CmsSiteDomain::getDb()->cache(function($db) {
                    return CmsSiteDomain::find()->where(['domain' => $this->_serverName])->one();
                }, null, $dependencySiteDomain);

                /**
                 * @var CmsSiteDomain $cmsDomain
                 */
                if ($cmsDomain) {
                    $this->_site = $cmsDomain->cmsSite;
                } else {

                    $this->_site = CmsSiteDomain::getDb()->cache(function($db) {
                        return CmsSite::find()->active()->andWhere(['def' => Cms::BOOL_Y])->one();
                    },
                        null,
                        new TagDependency([
                            'tags' =>
                                [
                                    (new CmsSite())->getTableCacheTag(),
                                ],
                        ])
                    );
                }
            }
        }

        return $this->_site;
    }

    /**
     * @param CmsSite $site
     * @return $this
     */
    public function set(CmsSite $site)
    {
        $this->_site = $site;
        return $this;
    }
}