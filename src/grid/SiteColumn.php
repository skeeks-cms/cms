<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (�����)
 * @date 01.09.2015
 */

namespace skeeks\cms\grid;

use skeeks\cms\models\CmsSite;
use yii\grid\DataColumn;

/**
 * Class SiteColumn
 * @package skeeks\cms\grid
 */
class SiteColumn extends DataColumn
{
    public $attribute = 'cms_site_id';

    /**
     * @inheritdoc
     */
    protected function renderDataCellContent($model, $key, $index)
    {
        if ($model->site && $model->site instanceof CmsSite) {
            $site = $model->site;
        } else {

        }

        if ($site) {
            return $site->name;
        }

        return null;
    }
}