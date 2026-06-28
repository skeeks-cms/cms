<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS
 */

use yii\db\Migration;

class m260628_110000__create_table__cms_user_favorite extends Migration
{
    public function safeUp()
    {
        $tableName = '{{%cms_user_favorite}}';
        $tableNameRaw = 'cms_user_favorite';
        $tableExist = $this->db->getTableSchema($tableName, true);

        if ($tableExist) {
            return true;
        }

        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }

        $this->createTable($tableName, [
            'id' => $this->primaryKey(),

            'created_at' => $this->integer()->null(),
            'updated_at' => $this->integer()->null(),

            'cms_user_id' => $this->integer()->notNull(),

            'entity_type' => $this->string(64)->notNull(),
            'entity_id' => $this->integer()->notNull(),

            'priority' => $this->integer()->notNull()->defaultValue(100),
        ], $tableOptions);

        $this->createIndex($tableNameRaw.'__cms_user_id', $tableName, 'cms_user_id');
        $this->createIndex($tableNameRaw.'__entity_type', $tableName, 'entity_type');
        $this->createIndex($tableNameRaw.'__entity_id', $tableName, 'entity_id');
        $this->createIndex($tableNameRaw.'__priority', $tableName, 'priority');
        $this->createIndex($tableNameRaw.'__user_entity', $tableName, ['cms_user_id', 'entity_type', 'entity_id'], true);

        $this->addCommentOnTable($tableName, 'Избранное пользователей');

        $this->addForeignKey(
            "{$tableNameRaw}__cms_user_id", $tableName,
            'cms_user_id', '{{%cms_user}}', 'id', 'CASCADE', 'CASCADE'
        );
    }

    public function safeDown()
    {
        echo "m260628_110000__create_table__cms_user_favorite cannot be reverted.\n";
        return false;
    }
}
