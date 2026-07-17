<?php

use yii\db\Migration;

class m260717_143000__alter_table__cms_contractor__add_registration_date extends Migration
{
    public function safeUp()
    {
        $tableName = 'cms_contractor';
        $schema = $this->db->getTableSchema($tableName, true);
        if ($schema && !isset($schema->columns['registration_date'])) {
            $this->addColumn($tableName, 'registration_date', $this->date()->null()->comment('Дата государственной регистрации'));
        }
    }

    public function safeDown()
    {
        $tableName = 'cms_contractor';
        $schema = $this->db->getTableSchema($tableName, true);
        if ($schema && isset($schema->columns['registration_date'])) {
            $this->dropColumn($tableName, 'registration_date');
        }
    }
}
