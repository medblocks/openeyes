<?php


class OEDbTestCase extends CDbTestCase
{
    public $test_tables = [];
    protected $_fixture_manager;
    protected $tear_down_callbacks = [];
    protected $can_create_tables = true;

    public function setUp()
    {
        parent::setUp();
        $this->setUpTraits();
    }

    public function setUpTraits()
    {
        $uses = array_flip(static::class_uses_recursive(static::class));

        if (isset($uses[WithFaker::class])) {
            $this->setUpFaker();
        }

        if (isset($uses[WithTransactions::class])) {
            $this->can_create_tables = false;
            $this->beginDatabaseTransaction();
        }
    }

    public function tearDownCallbacks($callback)
    {
        $this->tear_down_callbacks[] = $callback;
    }

    public function tearDown()
    {
        foreach ($this->tear_down_callbacks as $callback) {
            $callback();
            $this->can_create_tables = true;
        }

        $this->tear_down_callbacks = [];

        foreach (array_reverse($this->test_tables) as $table) {
            $this->dropTable($table);
        }

        parent::tearDown();
    }

    /**
     * Taken from laravel to borrow the approach for setting up testing traits
     *
     * @param $trait
     * @return array
     */
    public static function trait_uses_recursive($trait)
    {
        $traits = class_uses($trait);

        foreach ($traits as $trait) {
            $traits += static::trait_uses_recursive($trait);
        }

        return $traits;
    }

    /**
     * Taken from laravel to borrow the approach for setting up testing traits
     *
     * @param $class
     * @return array
     */
    public static function class_uses_recursive($class)
    {
        if (is_object($class)) {
            $class = get_class($class);
        }

        $results = [];

        foreach (array_merge([$class => $class], class_parents($class)) as $class) {
            $results += static::trait_uses_recursive($class);
        }

        return array_unique($results);
    }

    public function getFixtureManager()
    {
        if (!$this->_fixture_manager) {
            $this->_fixture_manager = Yii::app()->getComponent('fixture');
        }
        return $this->_fixture_manager;
    }

    protected function createTestTable($table, $fields, $foreign_keys = null)
    {
        if (!$this->can_create_tables) {
            $this->fail('Attempting to create a table inside a test transaction will cause an implicit commit');
        }

        $fields['id'] = 'int(11) NOT NULL AUTO_INCREMENT';
        $fields['created_user_id'] = 'int(10) unsigned NOT NULL default 1';
        $fields['last_modified_user_id'] = 'int(10) unsigned NOT NULL default 1';
        $fields['created_date'] = "datetime NOT NULL DEFAULT '1900-01-01 00:00:00'";
        $fields['last_modified_date'] = "datetime NOT NULL DEFAULT '1900-01-01 00:00:00'";
        $fields[] = 'PRIMARY KEY (id)';

        $connection = $this->getFixtureManager()->dbConnection;

        $connection->createCommand($connection->schema->createTable($table, $fields, 'ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci'))->execute();

        if (!empty($foreign_keys)) {
            foreach ($foreign_keys as $key_name => $def) {
                $connection->createCommand($connection->schema->addForeignKey($key_name, $table, $def[0], $def[1], $def[2]))->execute();
            }
        }

        $connection->createCommand($connection->schema->addForeignKey($table.'_cui_fk', $table, 'created_user_id', 'user', 'id'))->execute();
        $connection->createCommand($connection->schema->addForeignKey($table.'_lmui_fk', $table, 'last_modified_user_id', 'user', 'id'))->execute();

        $this->test_tables[] = $table;
    }

    protected function dropTable($table)
    {
        if (!$this->can_create_tables) {
            $this->fail('Attempting to drop table inside a test transaction will cause an implicit commit');
        }
        $this->getFixtureManager()->dbConnection->createCommand(
            $this->getFixtureManager()->dbConnection->schema->dropTable($table)
        )->execute();
    }

    /**
     * Helper method to retrieve a random lookup option
     *
     * @param $cls
     * @param int $count
     * @return mixed
     */
    protected function getRandomLookup($cls, $count = 1)
    {
        $criteria = new \CDbCriteria();
        $criteria->limit = 5 * $count;
        $all = $cls::model()->findAll($criteria);

        if ($count === 1) {
            return $all[array_rand($all)];
        }

        $result = [];
        foreach (array_rand($all, $count) as $i) {
            $result[] = $all[$i];
        }

        return $result;
    }
}
