<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\db;

use yii\db\Query;

/**
 * For a yii\db\Migration: repeatable up, honest down.
 *
 * MySQL does not roll DDL back, and the history row is written only after
 * safeUp(). A run stopped between two statements (a lost connection, a killed
 * deploy) is run again from the start, so every DDL step adds only what is
 * missing: a column, an index, a foreign key, a table. Its counterpart for
 * safeDown() drops only what exists, and refuseRollbackWithData() stops a
 * rollback before the first DDL while it would erase data users made.
 *
 * Every check reads a fresh schema: a cached one may still describe the table
 * before the previous statement.
 */
trait RepeatableMigrationTrait
{
    protected function hasTable(string $table): bool
    {
        return $this->db->getTableSchema($table, true) !== null;
    }

    protected function hasColumn(string $table, string $column): bool
    {
        $schema = $this->db->getTableSchema($table, true);

        return $schema !== null && $schema->getColumn($column) !== null;
    }

    protected function hasIndex(string $table, string $name): bool
    {
        if (!$this->hasTable($table)) {
            return false;
        }
        foreach ($this->db->getSchema()->getTableIndexes($table, true) as $index) {
            if ($index->name === $name) {
                return true;
            }
        }

        return false;
    }

    protected function hasForeignKey(string $table, string $name): bool
    {
        $schema = $this->db->getTableSchema($table, true);

        return $schema !== null && isset($schema->foreignKeys[$name]);
    }

    protected function createTableIfMissing(string $table, array $columns, $options = null): void
    {
        if ($this->hasTable($table)) {
            $this->skipped('table '.$table.' exists');

            return;
        }
        $this->createTable($table, $columns, $options);
    }

    protected function addColumnIfMissing(string $table, string $column, $type): void
    {
        if ($this->hasColumn($table, $column)) {
            $this->skipped('column '.$column.' of '.$table.' exists');

            return;
        }
        $this->addColumn($table, $column, $type);
    }

    protected function createIndexIfMissing(string $name, string $table, $columns, bool $unique = false): void
    {
        if ($this->hasIndex($table, $name)) {
            $this->skipped('index '.$name.' exists');

            return;
        }
        $this->createIndex($name, $table, $columns, $unique);
    }

    protected function addForeignKeyIfMissing(string $name, string $table, $columns, string $refTable, $refColumns, $delete = null, $update = null): void
    {
        if ($this->hasForeignKey($table, $name)) {
            $this->skipped('foreign key '.$name.' exists');

            return;
        }
        $this->addForeignKey($name, $table, $columns, $refTable, $refColumns, $delete, $update);
    }

    protected function dropTableIfExists(string $table): void
    {
        if ($this->hasTable($table)) {
            $this->dropTable($table);
        }
    }

    protected function dropColumnIfExists(string $table, string $column): void
    {
        if ($this->hasColumn($table, $column)) {
            $this->dropColumn($table, $column);
        }
    }

    protected function dropIndexIfExists(string $name, string $table): void
    {
        if ($this->hasIndex($table, $name)) {
            $this->dropIndex($name, $table);
        }
    }

    protected function dropForeignKeyIfExists(string $name, string $table): void
    {
        if ($this->hasForeignKey($table, $name)) {
            $this->dropForeignKey($name, $table);
        }
    }

    /**
     * Refuses the rollback before the first DDL while it would destroy data:
     * the schema stays as it is.
     *
     * @param array $checks label => a Query of the rows that would be lost; null — nothing to check
     *                      (the table or the column is already gone)
     * @throws \yii\base\Exception
     */
    protected function refuseRollbackWithData(array $checks): void
    {
        $found = [];
        foreach ($checks as $label => $query) {
            if (!$query instanceof Query) {
                continue;
            }
            $count = (int)$query->count('*', $this->db);
            if ($count > 0) {
                $found[] = $label.': '.$count;
            }
        }
        if ($found) {
            throw new \yii\base\Exception('Откат невозможен: он безвозвратно удалит данные CRM ('.implode('; ', $found).'). Схема не изменена. '
                .'Чтобы откатить: снимите дамп базы (mysqldump) и сохраните эти данные; если они действительно не нужны, удалите их вручную '
                .'и повторите откат. Вернуть их можно только из резервной копии базы.');
        }
    }

    private function skipped(string $what): void
    {
        if (!$this->compact) {
            echo '    > already done: '.$what."\n";
        }
    }
}
