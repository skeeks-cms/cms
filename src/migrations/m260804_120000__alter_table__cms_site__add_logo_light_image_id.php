<?php

use yii\db\Migration;

class m260804_120000__alter_table__cms_site__add_logo_light_image_id extends Migration
{
    public function safeUp()
    {
        $tableName = 'cms_site';
        $schema = $this->db->getTableSchema($tableName, true);

        if (!$schema || isset($schema->columns['logo_light_image_id'])) {
            return true;
        }

        $this->addColumn(
            $tableName,
            'logo_light_image_id',
            $this->integer()->null()->after('image_id')->comment('Логотип для светлого фона')
        );
        $this->createIndex($tableName.'__logo_light_image_id', $tableName, 'logo_light_image_id');
        $this->addForeignKey(
            $tableName.'__logo_light_image_id',
            $tableName,
            'logo_light_image_id',
            '{{%cms_storage_file}}',
            'id',
            'SET NULL',
            'SET NULL'
        );

        return true;
    }

    public function safeDown()
    {
        $tableName = 'cms_site';
        $schema = $this->db->getTableSchema($tableName, true);

        if (!$schema || !isset($schema->columns['logo_light_image_id'])) {
            return true;
        }

        $this->dropForeignKey($tableName.'__logo_light_image_id', $tableName);
        $this->dropIndex($tableName.'__logo_light_image_id', $tableName);
        $this->dropColumn($tableName, 'logo_light_image_id');

        return true;
    }
}
