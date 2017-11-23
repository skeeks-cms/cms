<?php
/**
 * Breadcrumbs
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 15.01.2015
 * @since 1.0.0
 */

namespace skeeks\cms\components;

use skeeks\cms\base\components\Descriptor;
use skeeks\cms\models\Site;
use skeeks\cms\models\Tree;
use skeeks\cms\models\TreeType;
use yii\base\Component;

/**
 * Class Cms
 * @package skeeks\cms\components
 */
class Breadcrumbs extends Component
{
    /**
     * @var array
     */
    public $parts = [];

    public function init()
    {
        parent::init();
    }

    /**
     * @param array $data
     * @return $this
     */
    public function append($data)
    {
        if (is_array($data)) {
            $this->parts[] = $data;
        } else {
            if (is_string($data)) {
                $this->parts[] = [
                    'name' => $data
                ];
            }
        }

        return $this;
    }

    /**
     * @param Tree $tree
     * @return $this
     */
    public function setPartsByTree(Tree $tree)
    {
        $parents = $tree->parents;
        $parents[] = $tree;

        foreach ($parents as $tree) {
            $this->append([
                'name' => $tree->name,
                'url' => $tree->url,
                'data' => [
                    'model' => $tree
                ],
            ]);
        }

        return $this;
    }

    public function createBase($baseData = [])
    {
        if (!$baseData) {
            $baseData = [
                'name' => \Yii::t('yii', 'Home'),
                'url' => '/'
            ];
        }

        $this->parts = [];

        $this->append($baseData);

        return $this;
    }

}