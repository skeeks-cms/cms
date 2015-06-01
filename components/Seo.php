<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 30.03.2015
 */
namespace skeeks\cms\components;
use skeeks\cms\base\Component;

use skeeks\cms\base\components\Descriptor;
use skeeks\cms\base\db\ActiveRecord;
use skeeks\cms\base\Module;
use skeeks\cms\models\Site;
use skeeks\cms\models\StorageFile;
use skeeks\cms\models\Tree;
use skeeks\cms\models\TreeType;
use skeeks\cms\models\User;
use skeeks\cms\widgets\Infoblock;
use skeeks\cms\widgets\StaticBlock;
use skeeks\sx\File;
use skeeks\sx\models\IdentityMap;
use Yii;
use yii\base\Event;
use yii\helpers\ArrayHelper;
use yii\web\UploadedFile;
use yii\web\View;

/**
 * Class Seo
 * @package skeeks\cms\components
 */
class Seo extends Component
{
    /**
     * Можно задать название и описание компонента
     * @return array
     */
    static public function descriptorConfig()
    {
        return array_merge(parent::descriptorConfig(), [
            'name'          => 'Seo компонент',
        ]);
    }

    /**
     * Файл с формой настроек, по умолчанию
     *
     * @return string
     */
    public function getConfigFormFile()
    {
        $class = new \ReflectionClass($this->className());
        return dirname($class->getFileName()) . DIRECTORY_SEPARATOR . 'seo/_form.php';
    }

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
     * @var bool
     */
    public $enableKeywordsGenerator = true;

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

    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['enableKeywordsGenerator', 'minKeywordLenth', 'maxKeywordsLength'], 'integer'],
        ]);
    }

    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'enableKeywordsGenerator'                => 'Автоматическая генерация ключевых слов',
            'minKeywordLenth'                        => 'Минимальная длина ключевого слова',
            'maxKeywordsLength'                      => 'Длинна ключевых слов',
        ]);
    }





    public function init()
    {
        parent::init();

        if (!$this->enableKeywordsGenerator)
        {
            return $this;
        }

        /**
         * Генерация SEO метатегов.
         * */
        \Yii::$app->view->on(View::EVENT_END_PAGE, function (Event $e) {
            if (!\Yii::$app->request->isAjax && !\Yii::$app->request->isPjax) {
                $this->generateBeforeOutputPage($e->sender);
            }
        });


        \Yii::$app->on(\skeeks\cms\modules\admin\Module::EVENT_READY, function (Event $e) {

        });
    }


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
