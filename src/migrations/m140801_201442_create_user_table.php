<?php
/**
 * m140801_201442_create_user_table
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 28.10.2014
 * @since 1.0.0
 */

use yii\db\Schema;
use yii\db\Migration;

/**
 * Class m140801_201442_create_user_table
 */
class m140801_201442_create_user_table extends Migration
{
    public function safeUp()
    {
        if ($this->db->driverName === 'mysql') {

            $filePath = __DIR__ . "/_mysql-migrations-dump.sql";

            $file = fopen($filePath, "r");
            if (!$file) {
                throw new \Exception("Unable to open file: '{$filePath}'");
            }
            $sql = fread($file, filesize($filePath));
            fclose($file);

            $this->db->pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, true);
            $this->compact = true;
            $this->execute($sql);

        } else if ($this->db->driverName === 'pgsql') {
            $filePath = __DIR__ . "/_pgsql-migrations-dump.sql";

            $file = fopen($filePath, "r");
            if (!$file) {
                throw new \Exception("Unable to open file: '{$filePath}'");
            }
            $sql = fread($file, filesize($filePath));
            fclose($file);

            $this->compact = true;
            $pdo = $this->db->masterPdo;
            $pdo->exec($sql);

        } else {
            echo "Error for driver {$this->db->driverName} cannot be reverted.\n";
            return false;
        }
    }

    public function down()
    {
        echo "m140801_201442_create_user_table cannot be reverted.\n";
        return false;
    }
}
