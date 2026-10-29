<?php
/**
 * Interrupts a migration at every statement it executes, as a lost connection
 * or a killed deploy would (MySQL does not roll DDL back, the history row is
 * written only after safeUp), and checks the recovery on a SEPARATE MySQL
 * database:
 *
 * - up, then up again: the schema does not change;
 * - up stopped after statement N, then up: the same schema as one clean up;
 * - up stopped after statement N, then down: the schema before the migration;
 * - down after a clean up: the schema before the migration.
 *
 * The schema is compared through information_schema: columns (type, null,
 * default, comment), indexes, foreign keys with their rules, tables with their
 * comments. Used by skeeks/cms tests/crm-migrations-repeat-stand.php and the
 * migration tests of the packages that depend on skeeks/cms.
 *
 *   $harness = require '<cms>/tests/support/migration-interrupt.php';
 *   $harness->check($db, 'm..._class', ['table', ...], $expect);
 */

namespace skeeks\cms\tests\support;

use yii\db\Command;
use yii\db\Connection;
use yii\db\Migration;

class MigrationInterrupted extends \RuntimeException
{
}

/** Stops before the N-th executed statement; queries are not counted. */
class InterruptingCommand extends Command
{
    public static int $limit = 0;
    public static int $executed = 0;

    public function execute()
    {
        if (self::$limit > 0 && ++self::$executed >= self::$limit) {
            throw new MigrationInterrupted('interrupted before statement '.self::$executed);
        }

        return parent::execute();
    }
}

return new class {
    /** @return array the schema of these tables */
    public function snapshot(Connection $db, array $tables): array
    {
        $in = implode(',', array_map([$db, 'quoteValue'], $tables));
        $schema = 'TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('.$in.')';
        $read = static function (string $sql) use ($db): array {
            $rows = $db->createCommand($sql)->queryAll();
            $rows = array_map(static fn(array $row) => implode('|', array_map('strval', $row)), $rows);
            sort($rows);

            return $rows;
        };

        return [
            'tables' => $read("SELECT TABLE_NAME, TABLE_COMMENT FROM information_schema.TABLES WHERE $schema"),
            'columns' => $read("SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COALESCE(COLUMN_DEFAULT, '<null>'), COLUMN_COMMENT FROM information_schema.COLUMNS WHERE $schema"),
            'indexes' => $read("SELECT TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX, COLUMN_NAME, NON_UNIQUE FROM information_schema.STATISTICS WHERE $schema"),
            'foreignKeys' => $read("SELECT k.TABLE_NAME, k.CONSTRAINT_NAME, k.COLUMN_NAME, k.REFERENCED_TABLE_NAME, k.REFERENCED_COLUMN_NAME, r.UPDATE_RULE, r.DELETE_RULE
                FROM information_schema.KEY_COLUMN_USAGE k JOIN information_schema.REFERENTIAL_CONSTRAINTS r
                    ON r.CONSTRAINT_SCHEMA = k.CONSTRAINT_SCHEMA AND r.CONSTRAINT_NAME = k.CONSTRAINT_NAME AND r.TABLE_NAME = k.TABLE_NAME
                WHERE k.TABLE_SCHEMA = DATABASE() AND k.TABLE_NAME IN ($in) AND k.REFERENCED_TABLE_NAME IS NOT NULL"),
        ];
    }

    /** Runs safeUp or safeDown quietly; with $limit — stopped before that statement. Returns the error or null. */
    public function run(Connection $db, string $class, string $method, int $limit = 0): ?string
    {
        $db->commandClass = InterruptingCommand::class;
        InterruptingCommand::$limit = $limit;
        InterruptingCommand::$executed = 0;
        $db->getSchema()->refresh();
        /** @var Migration $migration */
        $migration = new $class(['db' => $db, 'compact' => true]);
        ob_start();
        try {
            $result = $migration->$method();

            return $result === false ? 'returned false' : null;
        } catch (MigrationInterrupted $e) {
            return 'interrupted';
        } catch (\Throwable $e) {
            return get_class($e).': '.$e->getMessage();
        } finally {
            ob_end_clean();
            InterruptingCommand::$limit = 0;
            $db->getSchema()->refresh();
        }
    }

    /**
     * MariaDB adds and drops columns instantly and keeps the dropped ones in the row format until the
     * table is rebuilt: dozens of add/drop cycles on a wide table end in «Row size too large», which one
     * real run never meets. A rollback here rebuilds the tables, as the next real run would see them.
     */
    private function down(Connection $db, string $class, array $tables): ?string
    {
        $error = $this->run($db, $class, 'safeDown');
        foreach ($tables as $table) {
            if ($db->getTableSchema($table, true)) {
                $db->createCommand('ALTER TABLE '.$db->quoteTableName($table).' FORCE')->execute();
            }
        }
        $db->getSchema()->refresh();

        return $error;
    }

    /**
     * Every interruption point of the migration; leaves it applied.
     *
     * @param callable $expect function (bool $ok, string $what)
     * @return int the statements of a clean up
     */
    public function check(Connection $db, string $class, array $tables, callable $expect): int
    {
        $before = $this->snapshot($db, $tables);
        $error = $this->run($db, $class, 'safeUp');
        $expect($error === null, "$class: up".($error ? " — $error" : ''));
        $after = $this->snapshot($db, $tables);
        $expect($after !== $before, "$class: up changes the schema");
        $error = $this->run($db, $class, 'safeUp');
        $expect($error === null && $this->snapshot($db, $tables) === $after, "$class: up again changes nothing".($error ? " — $error" : ''));
        $error = $this->down($db, $class, $tables);
        $expect($error === null && $this->snapshot($db, $tables) === $before, "$class: down restores the schema before it".($error ? " — $error" : ''));

        $points = 0;
        for ($limit = 1; ; $limit++) {
            $error = $this->run($db, $class, 'safeUp', $limit);
            if ($error !== 'interrupted') {
                // It finished before this statement: every point is checked.
                $expect($error === null, "$class: up with no interruption left".($error ? " — $error" : ''));
                $error = $this->down($db, $class, $tables);
                $expect($error === null && $this->snapshot($db, $tables) === $before, "$class: down after it".($error ? " — $error" : ''));
                break;
            }
            $points++;
            $error = $this->run($db, $class, 'safeUp');
            $resumed = $error === null && $this->snapshot($db, $tables) === $after;
            $error2 = $this->down($db, $class, $tables);
            $this->run($db, $class, 'safeUp', $limit);
            $error3 = $this->down($db, $class, $tables);
            $rolledBack = $error3 === null && $this->snapshot($db, $tables) === $before;
            if (!$resumed || $error2 !== null || !$rolledBack) {
                $expect(false, "$class: stopped before statement $limit — up again ".($resumed ? 'ok' : 'differs'.($error ? " ($error)" : ''))
                    .', down '.($rolledBack ? 'ok' : 'differs'.($error3 ? " ($error3)" : '')));
            }
        }
        $expect($points > 0, "$class: $points interruption points, each resumed by up and reverted by down");
        $error = $this->run($db, $class, 'safeUp');
        $expect($error === null && $this->snapshot($db, $tables) === $after, "$class: applied again".($error ? " — $error" : ''));

        return $points;
    }
};
