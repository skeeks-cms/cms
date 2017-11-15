<?php
/**
 * SignupForm
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 28.10.2014
 * @since 1.0.0
 */

namespace skeeks\cms\models\forms;

use skeeks\cms\models\CmsUserEmail;
use skeeks\cms\models\User;
use yii\base\Model;
use Yii;

/**
 * Class ViewFileEditModel
 * @package skeeks\cms\models\forms
 */
class ViewFileEditModel extends Model
{
    public $rootViewFile;

    public $error;

    public $source;

    public function init()
    {
        parent::init();

        if (is_readable($this->rootViewFile) && file_exists($this->rootViewFile)) {
            $fp = fopen($this->rootViewFile, 'a+');
            if ($fp) {
                $content = fread($fp, filesize($this->rootViewFile));
                fclose($fp);
                $this->source = $content;

            } else {
                $this->error = "file is not exist or is not readable";
            }
        }
    }


    /**
     * @return array
     */
    public function attributeLabels()
    {
        return [
            'source' => \Yii::t('skeeks/cms', 'Code'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['rootViewFile', 'string'],
            ['source', 'string'],
        ];
    }

    /**
     * @return bool
     */
    public function saveFile()
    {
        if (is_writable($this->rootViewFile) && file_exists($this->rootViewFile)) {
            $file = fopen($this->rootViewFile, 'w');
            fwrite($file, $this->source);
            fclose($file);

            return true;
        }

        return false;
    }

}
