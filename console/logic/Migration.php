<?php
namespace console\logic;

use CW;
use Console;

class Migration {
    
    const MIGARTION_TABLE_NAME = 'migrations';

    const ANSWER_YES = 'yes';
    const ANSWER_NO = 'no';

    public static function create($params) {
        if (empty($params)) {
            throw new \Exception();
        }

        $migrationName = 'm' . time() . '_' . $params[0];
        var_dump($migrationName);

        $file = fopen(CW::$app->params['sitePath'] . 'console/migrations/' . $migrationName . '.php', 'w');

        fwrite($file, <<<CONTENT
<?php

class $migrationName extends \console\logic\BaseMigration {

    public function up() {
        // TODO generate migration up logic...
    }

    public function down() {
        // TODO generate migration down logic...
    }

}

CONTENT
        );

        fclose($file);
    }

    public static function up() {
        self::createMigrationTableIfNotExists();
        $pressentMigrations = self::getPressentMigration();
        $appliedMigrations = self::getAplliedMigration();

        $migrationsToApply = [];

        foreach ($pressentMigrations as $migration) {
            if (!in_array($migration, $appliedMigrations)) {
                $migrationsToApply[] = $migration;
            }
        }

        $migrationsToApply = array_reverse($migrationsToApply);

        if (!empty($migrationsToApply)) {
            Console::log("Migrations to apply:\n\n");
            Console::log('{0}', "Migrations to apply:\n\n", [Console::COLOR_GREEN]);

            foreach ($migrationsToApply as $migration) {
                echo "\t$migration\n";
            }
            
            Console::log("\nAre you sure you want to apply the migrations ? type {0}|{1}:\n", [self::ANSWER_YES, self::ANSWER_NO]);
            $answer = strtolower( trim( Console::in() ) );

            if (self::ANSWER_YES === $answer) {
                self::applyMigrations($migrationsToApply);
            } else {
                Console::log("Exiting.\n");
            }
        } else {
            Console::log('{0}', "No migrations to apply.", [Console::COLOR_GREEN]);
        }
    }

    private static function applyMigrations($migrations) {
        if (!empty($migrations) && is_array($migrations)) {
            echo "\n\n";

            foreach ($migrations as $migration) {
                require CW::$app->params['sitePath'] . "console/migrations/$migration.php";

                echo "Applying migration '$migration'...\n";
                $inst = new $migration();
                $inst->up();
                echo "INSERT INTO `" . self::MIGARTION_TABLE_NAME . "` (`name`, `time`) VALUES ('$migration', " . time() . ")\n";
                var_dump(CW::$app->db->executeUpdate("INSERT INTO `" . self::MIGARTION_TABLE_NAME . "` (`name`, `time`) VALUES ('$migration', " . time() . ")"));
                Console::log('{0}', "Migration '$migration' applied.\n\n", Console::COLOR_GREEN);
            }
        }
    }

    private static function getPressentMigration() {
        $pressentMigrations = [];

        $dh  = opendir(CW::$app->params['sitePath'] . 'console/migrations');

        while (false !== ($filename = readdir($dh))) {
            if ($filename == '.' || $filename == '..') {
                continue;
            }
            $a = explode('.', $filename);
            if ($a[1] == 'php') {
                $pressentMigrations[] = $a[0];
            }
        }

        return $pressentMigrations;
    }

    private static function getAplliedMigration() {
        $appliedMigrations = [];

        if ($stmt = CW::$app->db->executeQuery('SELECT `name` FROM `' . self::MIGARTION_TABLE_NAME . '`')) {
            foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $result) {
                $appliedMigrations[] = $result['name'];
            }
        }

        return $appliedMigrations;
    }

    private static function createMigrationTableIfNotExists() {
        if (!self::doesMigrationTableExist()) {
            $tableName = self::MIGARTION_TABLE_NAME;

            return CW::$app->db->executeUpdate(<<<QUERY
                    CREATE TABLE `$tableName` (
                        `id` INT PRIMARY KEY AUTO_INCREMENT,
                        `name` VARCHAR(255) NOT NULL UNIQUE,
                        `time` INTEGER NOT NULL
                    )ENGINE=InnoDB;
QUERY
            );
        }

        return false;
    }

    private static function doesMigrationTableExist() {
        return in_array(self::MIGARTION_TABLE_NAME, self::getTables());
    }

    private static function getTables() {
        if ($stmt = CW::$app->db->executeQuery('show tables')) {
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }

        return [];
    }
}
