<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Schema;
use yii\db\Migration;

class m190621_015515__alter_table__cms_site_domain extends Migration
{

    public function safeUp()
    {
        $this->addColumn("{{%cms_site_domain}}", "is_main", $this->integer(1)->unsigned()->comment("Основной домен?"));
        $this->addColumn("{{%cms_site_domain}}", "is_https", $this->integer(1)->unsigned()->comment("Работает через https?"));

        $this->createIndex("is_https", "{{%cms_site_domain}}", "is_https");
        $this->createIndex("is_main", "{{%cms_site_domain}}", "is_main");
        $this->createIndex("is_main_unique_for_site", "{{%cms_site_domain}}", ["is_main", "cms_site_id"], true);
    }

    public function safeDown()
    {
        echo "m190621_015515__alter_table__cms_site_domain cannot be reverted.\n";
        return false;
    }
}