<?php
namespace console\logic;

use CW;

abstract class BaseMigration {

    public function up() {}

    public function down() {}

    protected final function createTable($tableName, $options, $tableInfo = '') {
        \Console::log("Creating table {0}...\n", [$tableName], [\Console::FG_YELLOW]);

        if (is_array($options)) {
            $a = [];

            foreach ($options as $key => $value) {
                $a[] = is_string($key) ? "`$key` $value" : $value;
            }

            $options = implode(',', $a);
        }

        $query = "CREATE TABLE `$tableName` ($options) $tableInfo";
        \Console::log("$query\n\n");
        CW::$app->db->executeUpdate($query);
    }

}
