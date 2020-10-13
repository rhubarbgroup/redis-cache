<?php
// backward compatibility (https://stackoverflow.com/a/42828632/187780)
if (!class_exists('\PHPUnit\Framework\TestCase') && class_exists('\PHPUnit_Framework_TestCase')) {
    class_alias('\PHPUnit_Framework_TestCase', '\PHPUnit\Framework\TestCase');
}

class CredisTestCommon extends \PHPUnit\Framework\TestCase
{
    protected $useStandalone = false;
    protected $redisConfig = null;
    protected $slaveConfig = null;

    protected function setUp()
    {
        if ($this->redisConfig === null)
        {
            $configFile = dirname(__FILE__) . '/redis_config.json';
            if (!file_exists($configFile) || !($config = file_get_contents($configFile)))
            {
                $this->markTestSkipped('Could not load ' . $configFile);

                return;
            }
            $this->redisConfig = json_decode($config);
            $arrayConfig = array();
            foreach ($this->redisConfig as $config)
            {
                $arrayConfig[] = (array)$config;
            }
            $this->redisConfig = $arrayConfig;
        }

        if(!$this->useStandalone && !extension_loaded('redis')) {
            $this->fail('The Redis extension is not loaded.');
        }
    }

    /**
     * Verifies the slave has connected to the master and replication has caught up
     *
     * @return bool
     */
    protected function waitForSlaveReplication()
    {
        if ($this->slaveConfig === null)
        {
            foreach ($this->redisConfig as $config)
            {
                if ($config['alias'] === 'slave')
                {
                    $this->slaveConfig = $config;
                    break;
                }
            }
            if ($this->slaveConfig === null)
            {
                $this->markTestSkipped('Could not load slave config');

                return false;
            }
        }
        $masterConfig = new Credis_Client($this->redisConfig[0]['host'], $this->redisConfig[0]['port']);
        $masterConfig->forceStandalone();

        $slaveConfig = new Credis_Client($this->slaveConfig['host'], $this->slaveConfig['port']);
        $slaveConfig->forceStandalone();

        $start = microtime(true);
        $timeout = $start + 60;
        while (microtime(true) < $timeout)
        {
            usleep(100);
            $role = $slaveConfig->role();
            if ($role[0] !== 'slave')
            {
                $this->markTestSkipped('slave config does not points to a slave');
                return false;
            }
            if ($role[3] === 'connected')
            {
                $masterRole = $masterConfig->role();
                if ($masterRole[0] !== 'master')
                {
                    $this->markTestSkipped('master config does not points to a master');
                    return false;
                }
                if ($role[4] >= $masterRole[1])
                {
                    return true;
                }
            }
        }
        // shouldn't get here
        $this->fail("Timeout (".(microtime(true) - $start)." seconds) waiting for master-slave replication to finalize");
        return false;
    }

    public static function setUpBeforeClass()
    {
        if(preg_match('/^WIN/',strtoupper(PHP_OS))){
            echo "Unit tests will not work automatically on Windows. Please setup all Redis instances manually:".PHP_EOL;
            echo "\tredis-server redis-master.conf".PHP_EOL;
            echo "\tredis-server redis-slave.conf".PHP_EOL;
            echo "\tredis-server redis-2.conf".PHP_EOL;
            echo "\tredis-server redis-3.conf".PHP_EOL;
            echo "\tredis-server redis-4.conf".PHP_EOL;
            echo "\tredis-server redis-auth.conf".PHP_EOL;
            echo "\tredis-server redis-socket.conf".PHP_EOL.PHP_EOL;
        } else {
            chdir(__DIR__);
            $directoryIterator = new DirectoryIterator(__DIR__);
            foreach($directoryIterator as $item){
                if(!$item->isfile() || !preg_match('/^redis\-(.+)\.conf$/',$item->getFilename()) || $item->getFilename() == 'redis-sentinel.conf'){
                    continue;
                }
                exec('redis-server '.$item->getFilename());
            }
            copy('redis-master.conf','redis-master.conf.bak');
            copy('redis-slave.conf','redis-slave.conf.bak');
            // wait for redis instances to initialize
            sleep(1);
        }
    }

    public static function tearDownAfterClass()
    {
        if(preg_match('/^WIN/',strtoupper(PHP_OS))){
            echo "Please kill all Redis instances manually:".PHP_EOL;
        } else {
            chdir(__DIR__);
            $directoryIterator = new DirectoryIterator(__DIR__);
            foreach($directoryIterator as $item){
                if(!$item->isfile() || !preg_match('/^redis\-(.+)\.pid$/',$item->getFilename())){
                    continue;
                }
                $pid = trim(file_get_contents($item->getFilename()));
                if(function_exists('posix_kill')){
                    posix_kill($pid,15);
                } else {
                    exec('kill '.$pid);
                }
            }
            sleep(1); // give teardown some time to finish
            @unlink('dump.rdb');
            @unlink('redis-master.conf');
            @unlink('redis-slave.conf');
            @copy('redis-master.conf.bak','redis-master.conf');
            @copy('redis-slave.conf.bak','redis-slave.conf');
        }
    }

    /**
     * php 7.2 compat fix, as directly polyfilling for older PHPUnit causes a function signature compatibility issue
     * This is due to the defined return type
     */
    public function setExpectedExceptionShim($class, $message = NULL, $code = NULL)
    {
        if (method_exists($this, 'setExpectedException')) {
            $this->setExpectedException($class, $message, $code);
        } else {
            parent::expectException($class);
            if ($message !== null) {
                $this->expectExceptionMessage($message);
            }
            if ($code !== null) {
                $this->expectExceptionCode($code);
            }
        }
    }
}
