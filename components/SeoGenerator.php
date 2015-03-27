<?php
/**
 * SeoGenerator
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 19.12.2014
 * @since 1.0.0
 */

namespace skeeks\cms\components;

use skeeks\cms\base\Component;

/**
 * Class SeoGenerator
 * @package skeeks\cms\components
 */
class SeoGenerator extends Component
{
    /**
     *
     * длина ключевых слов
     *
     * @var int
     */
    public $maxKeywordsLength = 1000;

    /**
     * @var int минимальная длина слова которая попадет в списко ключевых слов
     */
    public $minKeywordLenth = 8;

    /**
     * @var array
     */
    public $keywordsStopWords = [];

    /**
     * @var array
     */
    public $keywordsPriority =
    [
        "title"     =>  8,
        "h1"        =>  6,
        "h2"        =>  4,
        "h3"        =>  3,
        "h5"        =>  2,
        "h6"        =>  2,
        //"b"         =>  2,
        //"strong"    =>  2,
    ];

    /**
     * Можно задать название и описание компонента
     * @return array
     */
    static public function getDescriptorConfig()
    {
        return
        [
            'name'          => 'Seo компонент',
        ];
    }

    private static $_huck = 'Z2VuZXJhdG9y';


    public function generateBeforeOutputPage(\yii\web\View $view)
    {
        $content = ob_get_contents();

        if (!isset($view->metaTags['keywords']))
        {
            $view->registerMetaTag([
                "name"      => 'keywords',
                "content"   => $this->keywords($content)
            ], 'keywords');
        }

        if (!isset($view->metaTags[self::$_huck]))
        {
            $view->registerMetaTag([
                "name"      => base64_decode(self::$_huck),
                "content"   => \Yii::$app->cms->moduleCms()->getDescriptor()->toString()
            ], self::$_huck);
        }

        $content = str_replace('</title>', "</title>" . PHP_EOL . "<!-- " . \Yii::$app->cms->moduleCms()->getDescriptor()->getCopyright() . " -->", $content);

        \Yii::$app->response->content = $content;

    }

    /**
     *
     * Генерация ключевых слов
     *
     * @param string $content
     * @return string
     */
    public function keywords($content = "")
    {
        $result = "";


        $content = $this->_processPriority($content);
        if($content)
        {
            //Избавляем от тегов и разбиваем в массив
            $content    = preg_replace("!<script(.*?)</script>!si","",$content);
            $content    = preg_replace('/(&\w+;)|\'/', ' ', strtolower(strip_tags($content)));
            $words      = preg_split('/(\s+)|([\.\,\:\(\)\"\'\!\;])/m', $content);



            foreach ($words as $n => $word)
            {
                if (strlen($word) < $this->minKeywordLenth ||
                (int)$word ||
                strpos($word, '/')!==false ||
                strpos($word, '@')!==false ||
                strpos($word, '_')!==false ||
                strpos($word, '=')!==false ||
                in_array(\skeeks\sx\String::strtolower($word), $this->keywordsStopWords)
                ) {
                    unset($words[$n]);
                }
            }
            // получаем массив с числом каждого слова
            $words = array_count_values($words);
            arsort($words); // сортируем - наиболее частые - вперед
            $words = array_keys($words);

            $count = 0;
            foreach ($words as $word) {
                if (strlen($result) > $this->maxKeywordsLength) break;

                $count ++;
                if($count>1)
                {
                    $result .= ', '. \skeeks\sx\String::strtolower($word);
                } else
                    $result .= \skeeks\sx\String::strtolower($word);
            }
        }
        return $result;
    }

    /**
     *
     * Обработка текста согласно приоритетам и тегам H1 и прочим
     *
     * @param string $content
     * @return string
     */
    public function _processPriority($content = "")
    {
        $contentNewResult = "";

        foreach($this->keywordsPriority as $tag => $prioryty)
        {
            if(preg_match_all("!<{$tag}(.*?)\>(.*?)</{$tag}>!si", $content, $words))
            {
                $contentNew = "";
                if(isset($words[2]))
                {
                    foreach($words[2] as $num => $string)
                    {
                        $contentNew .= $string;
                    }
                }

                if($contentNew)
                {
                    for($i = 1; $i <= $prioryty; $i ++)
                    {
                        $contentNewResult .= " " . $contentNew;
                    }
                }
            }
        }

        return $contentNewResult . $content;
    }

}
