<?php
/**
 * m141205_100557_alter_table_published_behavior
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 09.12.2014
 * @since 1.0.0
 */

use yii\db\Schema;
use yii\db\Migration;

/**
 * Class m141205_100557_alter_table_published_behavior
 */
class m141205_100557_alter_table_published_behavior extends Migration
{
    public function up()
    {
        $this->addColumn('{{%cms_storage_file}}', 'published_at', Schema::TYPE_INTEGER . ' NULL');
        $this->addColumn('{{%cms_publication}}', 'published_at', Schema::TYPE_INTEGER . ' NULL');
        $this->addColumn('{{%cms_tree}}', 'published_at', Schema::TYPE_INTEGER . ' NULL');

        $this->execute("ALTER TABLE {{%cms_storage_file}} ADD INDEX(published_at);");
        $this->execute("ALTER TABLE {{%cms_publication}} ADD INDEX(published_at);");
        $this->execute("ALTER TABLE {{%cms_tree}} ADD INDEX(published_at);");
    }

    public function down()
    {
        $this->dropColumn('{{%cms_storage_file}}', 'published_at');
        $this->dropColumn('{{%cms_publication}}', 'published_at');
        $this->dropColumn('{{%cms_tree}}', 'published_at');
    }
}
