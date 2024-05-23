<?php

class Main
{
    const BLOCK_CLASSES = [
        'ErrorCode',
        'BizLogUtils',
        'Utils',
        'ApiController',
        'BaseConstants',
        'ModelType',
        'BusinessException',
        'CustomDimension',
    ];

    public function __construct()
    {
        require_once "/Users/haoxingxiao/repos/code_analysis/CodeReader.php";
        require_once "/Users/haoxingxiao/repos/code_analysis/GraphDb.php";
    }

    public function run()
    {
        $reader = new CodeReader();
        $classes = $reader->getAllFiles();
        print_r(sizeof($classes) . " files found.\r\n");
        $entities = $reader->parse($classes);
        print_r(sizeof($entities) . " classes parsed.\r\n");
        GraphDb::init();
        $db = new GraphDb();
        $db->clear();
        print_r("Neo4J Database init.\r\n");
        $count = count($entities);
        $createCount = 0;
        $edgeCount = 0;
        foreach ($entities as $idx => $entity) {
            if (!$db->hasNode($entity['type'], $entity) && !in_array($entity['class'], self::BLOCK_CLASSES)) {
                $str = sprintf('[%3d/%3d]generating %s node in db...', $idx, $count, $entity['class']);
                echo $str . "\r\n";
                $db->createNode($entity['type'], $entity);
                $createCount += 1;
            } else {
                $str = sprintf('[%3d/%3d]%s node skipped...', $idx, $count, $entity['class']);
                echo $str . "\r\n";
            }
            $edgeCount += count($entity['use']);
        }
        echo sprintf("%d/%d nodes generated.\r\n", $createCount, $count);
        $step = 0;
        $createCount = 0;
        foreach ($entities as $entity) {
            if (isset($entity['use'])) {
                foreach ($entity['use'] as $use) {
                    $toParams = ['use_format' => $use];
                    if ($db->hasNode('', $toParams)) {
                        $fromParams = ['class' => $entity['class'], 'namespace' => $entity['namespace']];
                        $db->createEdge($entity['type'], $fromParams, 'use', [], null, $toParams);
                        $str = sprintf('[%4d/%4d]generating %s edge in db...', $step, $edgeCount, $entity['class']);
                        echo $str . "\r";
                        $createCount += 1;
                    } else {
                        $str = sprintf('[%4d/%4d] %s edge to outer class, skip...', $step, $edgeCount, $entity['class']);
                        echo $str . "\r\n";
                    }
                    $step += 1;
                }
            }
        }
        echo sprintf("%d/%d edges generated.\r\n", $createCount, $edgeCount);
        echo "All Generation has done!!!! Visit: http://localhost:7474 to view your dependency graph.\r\n";
        echo "Cypher examples:\r\n";
        echo "\t1. View the whole graph: match (a)-[e]-(b) return a,e,b;\r\n";
        echo "\t2. View classes dependencies with unknown types: match (a:class)-[e]->(b) return a,e,b union match (a)-[e]->(b:class) return a,e,b;\r\n";
    }
}

$application = new Main();
$application->run();