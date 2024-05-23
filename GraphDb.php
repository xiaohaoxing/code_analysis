<?php

use Laudis\Neo4j\Authentication\Authenticate;
use Laudis\Neo4j\ClientBuilder;
use Laudis\Neo4j\Databags\Statement;

require_once 'vendor/autoload.php';

class GraphDb
{
    protected static $client;

    static function init()
    {
        self::$client = ClientBuilder::create()
            ->withDriver('bolt', 'bolt://neo4j:12345678@localhost')
            ->withDriver('http', 'http://localhost:7474', Authenticate::basic('neo4j', '12345678'))
            ->withDefaultDriver('bolt')
            ->build();
    }

    public function readTest()
    {
        $results = self::$client->run('MATCH (user:user{name:\'xhx\'}) RETURN user');

// A row is a CypherMap
        foreach ($results as $result) {
            $user = $result->get('user');

            echo $user->getProperty('name');
        }
    }

    public function createNode($type, array $params)
    {
        $json = ext_json_encode($params);
        $cmd = new Statement(sprintf("create (a%s%s) return a", $type ? ':' . $type : '', $json), []);
        self::$client->runStatement($cmd);
    }

    public function hasNode($type, array $params)
    {
        $node = $this->readNode($type, $params);
        return !$node->isEmpty();
    }

    public function readNode($nodeType, $nodeParams)
    {
        $cmd = sprintf("match (a%s%s) return a", $nodeType ? ':' . $nodeType : '', ext_json_encode($nodeParams));
        return self::$client->run($cmd);
    }

    public function createEdge($fromType, $fromParams, $edgeType, $edgeParams, $toType, $toParams)
    {
        $cmd = new Statement(sprintf("match (a%s%s) match (b%s%s) create (a)-[e%s%s]->(b)",
            $fromType ? ':' . $fromType : '',
            ext_json_encode($fromParams),
            $toType ? ':' . $toType : '',
            ext_json_encode($toParams),
            $edgeType ? ':' . $edgeType : '',
            $edgeParams ? ext_json_encode($edgeParams) : '{}'), []);
        self::$client->runStatement($cmd);
    }

    public function deleteNode($type, array $params)
    {
        $cmd = new Statement(sprintf("MATCH (a:%s%s) DELETE a", $type, ext_json_encode($params)), []);
        self::$client->runStatement($cmd);
    }

    public function update($filters, $params)
    {

    }

    public function clear()
    {
        $cmd = 'match (a)-[e]->(b) delete a,e,b';
        self::$client->run($cmd);
    }
}

function ext_json_encode($obj)
{
    $json = json_encode($obj);
    if (preg_match('/"\w+":/', $json)) {
        $json = preg_replace('/(")(\w+)("):/', '$2:', $json);
    }
    return $json;
}


//GraphDb::init();
//$db = new GraphDb();
//$nodes = $db->readNode('user', ['name' => 'ppp']);
//if ($nodes->isEmpty()) {
//    var_dump("nothing!");
//} else {
//    foreach ($nodes as $node) {
//        $user = $node->get('user');
//        var_dump($user->getProperty('name'));
//    }
//}
