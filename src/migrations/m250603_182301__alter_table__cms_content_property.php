<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m250603_182301__alter_table__cms_content_property extends Migration
{
    public function safeUp()
    {
        //$tableName = '{{%cms_session}}';
        $tableName = 'cms_content_property';

        $subQuery = $this->db->createCommand("
            UPDATE 
                `cms_content_property` as c
            SET 
                c.is_offer_property = 0
            WHERE c.is_offer_property is null
        ")->execute();

        $this->alterColumn($tableName, "is_offer_property", $this->integer(1)->notNull()->defaultValue(0)->comment("Свойство модификации"));

    }

    public function safeDown()
    {
        echo self::class;
        return false;
    }
}