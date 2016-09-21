<?php
defined('UNIT_TESTS_ROOT') || require __DIR__ . '/bootstrap.php';

class HttpSessionAdapterDbTest extends TestCase
{
    public function setUp()
    {
        parent::setUp(); // TODO: Change the autogenerated stub

        $di = new \ManaPHP\Di\FactoryDefault();
        $di->setShared('db', function () {
            $config = require __DIR__ . '/config.database.php';
            $db = new ManaPHP\Db\Adapter\Mysql($config['mysql']);
            //   $db = new ManaPHP\Db\Adapter\Sqlite($config['sqlite']);

            $db->attachEvent('db:beforeQuery', function (ManaPHP\DbInterface $source) {
                var_dump($source->getSQL());
                var_dump($source->getEmulatedSQL());
            });

            echo get_class($db), PHP_EOL;
            return $db;
        });
    }

    public function test_open()
    {
        $session_id = md5(microtime(true) . mt_rand());
        $adapter = new \ManaPHP\Http\Session\Adapter\Db(['ttl' => 3600]);

        $this->assertTrue($adapter->open('', $session_id));
    }

    public function test_close()
    {
        $session_id = md5(microtime(true) . mt_rand());
        $adapter = new \ManaPHP\Http\Session\Adapter\Db(['ttl' => 3600]);

        $this->assertTrue($adapter->close());
    }

    public function test_read()
    {
        $session_id = md5(microtime(true) . mt_rand());
        $adapter = new \ManaPHP\Http\Session\Adapter\Db();

        $adapter->open($session_id, '');
        $this->assertEquals('', $adapter->read($session_id));

        $adapter->write($session_id, 'manaphp');
        $this->assertEquals('manaphp', $adapter->read($session_id));
    }

    public function test_write()
    {
        $session_id = md5(microtime(true) . mt_rand());
        $adapter = new \ManaPHP\Http\Session\Adapter\Db();

        $adapter->write($session_id, '');
        $this->assertEquals('', $adapter->read($session_id));

        $adapter->write($session_id, 'manaphp');
        $this->assertEquals('manaphp', $adapter->read($session_id));
    }

    public function test_destory()
    {
        $session_id = md5(microtime(true) . mt_rand());
        $adapter = new \ManaPHP\Http\Session\Adapter\Db();
        $this->assertTrue($adapter->destroy($session_id));

        $adapter->write($session_id, 'manaphp');
        $this->assertEquals('manaphp', $adapter->read($session_id));
        $this->assertTrue($adapter->destroy($session_id));

        $this->assertEquals('', $adapter->read($session_id));
    }

    public function test_gc()
    {
        $session_id = md5(microtime(true) . mt_rand());
        $adapter = new \ManaPHP\Http\Session\Adapter\Db();
        $this->assertTrue($adapter->gc(100));
    }
}