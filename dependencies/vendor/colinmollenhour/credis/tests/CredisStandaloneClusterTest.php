<?php

require_once dirname(__FILE__).'/CredisClusterTest.php';

class CredisStandaloneClusterTest extends CredisClusterTest
{
  protected $useStandalone = TRUE;
  protected function tearDown()
  {
    if($this->cluster) {
        foreach($this->cluster->clients() as $client){
            if($client->isConnected()) {
                $client->close();
            }
        }
        $this->cluster = NULL;
    }
  }
  public function testMasterSlave()
  {
    $this->tearDown();
    $this->cluster = new Credis_Cluster(array($this->redisConfig[0],$this->redisConfig[6]), 2, $this->useStandalone);
    $this->assertTrue($this->cluster->client('master')->set('key','value'));
    $this->waitForSlaveReplication();
    $this->assertEquals('value',$this->cluster->client('slave')->get('key'));
    $this->assertEquals('value',$this->cluster->get('key'));
    $this->setExpectedExceptionShim('CredisException','READONLY You can\'t write against a read only replica.');
    $this->cluster->client('slave')->set('key2','value');
  }
}
