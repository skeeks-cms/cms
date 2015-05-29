<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 24.03.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m150520_163310_insert_cms_site_and_lang extends Migration
{
    public function safeUp()
    {
        $this->execute(<<<SQL
INSERT INTO `cms_lang` (`id`, `created_by`, `updated_by`, `created_at`, `updated_at`, `active`, `def`, `priority`, `code`, `name`, `description`) VALUES
(1, 1, 1, 1432126580, 1432130752, 'Y', 'Y', 500, 'ru', 'Русский', ''),
(2, 1, 1, 1432126667, 1432130744, 'N', 'N', 600, 'en', 'Английский', '');

SQL
);

$this->execute(<<<SQL
INSERT INTO `cms_site` (`id`, `created_by`, `updated_by`, `created_at`, `updated_at`, `active`, `def`, `priority`, `code`, `lang_code`, `name`, `server_name`, `description`) VALUES
(1, 1, 1, 1432128290, 1432130861, 'Y', 'Y', 500, 's1', 'ru', 'Сайт 1', '', '');
SQL
);

    }

    public function down()
    {
        echo "m150520_163310_insert_cms_site_and_lang cannot be reverted.\n";
        return false;
    }
}
