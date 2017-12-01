<?php
/**
 * Добавляем типы публикаций и страниц
 * m141111_100557_alter_tables_tree_and_publication
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 11.11.2014
 * @since 1.0.0
 */

use yii\db\Schema;
use yii\db\Migration;

class m141111_100557_alter_tables_tree_and_publication extends Migration
{
    public function up()
    {
        $this->addColumn('{{%cms_publication}}', 'type', Schema::TYPE_STRING . '(32) NULL');
        $this->addColumn('{{%cms_publication}}', 'tree_ids', Schema::TYPE_STRING . '(500) NULL');
        $this->addColumn('{{%cms_tree}}', 'type', Schema::TYPE_STRING . '(32) NULL');

        $this->execute("ALTER TABLE {{%cms_publication}} ADD INDEX(type);");
        $this->execute("ALTER TABLE {{%cms_publication}} ADD INDEX(tree_ids);");
        $this->execute("ALTER TABLE {{%cms_tree}} ADD INDEX(type);");


        $this->execute(<<<SQL
INSERT INTO `cms_tree` (`id`, `created_by`, `updated_by`, `created_at`, `updated_at`, `name`, `description_short`, `description_full`, `files`, `page_options`, `seo_page_name`, `count_comment`, `count_subscribe`, `users_subscribers`, `count_vote`, `result_vote`, `users_votes_up`, `users_votes_down`, `status`, `status_adult`, `pid`, `pid_main`, `pids`, `level`, `dir`, `has_children`, `main_root`, `priority`, `type`) VALUES
(1, 1, 1, 1416084922, 1416839464, 'Главная страница', '', '', '', '{"_":{"meta_title":{"value":"Главная страница по умолчанию"},"meta_keywords":{"value":"Главная страница по умолчанию"},"meta_description":{"value":"Главная страница по умолчанию"}}}', NULL, NULL, NULL, NULL, NULL, NULL, '', '', 10, 0, NULL, NULL, '', 0, NULL, 1, 1, 0, 'homePage');
SQL
);

    }

    public function down()
    {
        $this->dropColumn('{{%cms_publication}}', 'type');
        $this->dropColumn('{{%cms_publication}}', 'tree_ids');
        $this->dropColumn('{{%cms_tree}}', 'type');

    }
}
