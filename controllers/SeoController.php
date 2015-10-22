<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 07.06.2015
 */
namespace skeeks\cms\controllers;

use skeeks\cms\base\Controller;
use skeeks\cms\models\CmsContentElement;
use skeeks\cms\models\Tree;
use Yii;
use yii\web\Response;

/**
 * Class SeoController
 * @package skeeks\cms\controllers
 */
class SeoController extends Controller
{
    /**
     * Динамическая отдача robots.txt
     */
    public function actionRobots()
    {
        echo \Yii::$app->seo->robotsContent;
        \Yii::$app->response->format = Response::FORMAT_RAW;
        \Yii::$app->response->headers->set('Content-Type', 'text/plain');
        $this->layout = false;
    }

    /**
     * Стандартный sitemap
     */
    public function actionSitemap()
    {
        ini_set("memory_limit","512M");

        $trees = Tree::find()->where(['site_code' => \Yii::$app->cms->site->code])->orderBy(['level' => SORT_ASC, 'priority' => SORT_ASC])->all();

        if ($trees)
        {
            /**
             * @var Tree $tree
             */
            foreach ($trees as $tree)
            {
                if (!$tree->redirect)
                {
                    $result[] =
                    [
                        "loc"           => $tree->url,
                        "lastmod"       => $this->_lastMod($tree),
                        "priority"      => $this->_calculatePriority($tree),
                        "changefreq"    => "daily",
                    ];
                }
            }
        }

        $elements = CmsContentElement::find()
                    ->joinWith('cmsTree')
                    ->andWhere([Tree::tableName() . '.site_code' => \Yii::$app->cms->site->code])
                    ->orderBy(['updated_at' => SORT_DESC, 'priority' => SORT_ASC])
                    ->all();
        //Добавление элементов в карту
        if ($elements)
        {
            /**
             * @var CmsContentElement $model
             */
            foreach ($elements as $model)
            {
                $result[] =
                [
                    "loc"           => $model->absoluteUrl,
                    "lastmod"       => $this->_lastMod($model),
                    "priority"      => "0.3",
                    "changefreq"    => "daily",
                ];
            }
        }

        \Yii::$app->response->format = Response::FORMAT_XML;
        $this->layout = false;

        //Генерация sitemap вручную, не используем XmlResponseFormatter
        \Yii::$app->response->content =  $this->render($this->action->id, [
            'data' => $result
        ]);

        return;

        /*return $this->render($this->action->id, [
            'data' => $result
        ]);;*/
    }

    /**
     * @param Tree $model
     * @return string
     */
    private function _lastMod($model)
    {
        $string = "2013-08-03T21:14:41+01:00";
        $string = date("Y-m-d", $model->updated_at) . "T" . date("H:i:s+04:00", $model->updated_at);

        return $string;
    }

    /**
     * @param Tree $model
     * @return string
     */
    private function _calculatePriority($model)
    {
        $priority = '0.4';
        if ($model->level == 0)
        {
            $priority = '1.0';
        } else if($model->level == 1)
        {
            $priority = '0.8';
        } else if($model->level == 2)
        {
            $priority = '0.7';
        } else if($model->level == 3)
        {
            $priority = '0.6';
        } else if($model->level == 4)
        {
            $priority = '0.5';
        }

        return $priority;
    }
}
