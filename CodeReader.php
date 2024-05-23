<?php


class CodeReader
{
    protected $project = '/Users/haoxingxiao/repos/tennispark-user-api';
    protected $path = 'app';

    public function getAllFiles()
    {
        $dir = $this->project . '/' . $this->path;
        $allFiles = [];
        $this->getAllSubFiles($dir, $allFiles);
        return $allFiles;
    }

    public function parse(array $paths)
    {
        $entities = [];
        foreach ($paths as $path) {
            $entity = $this->readFile($path);
            if (!empty($entity)) {
                $entities[] = $entity;
            }
        }
        return $entities;
    }

    private function getAllSubFiles($path, &$files)
    {
        if (is_dir($path)) {
            $dp = dir($path);
            while ($file = $dp->read()) {
                if ($file != '.' && $file != '..') {
                    $this->getAllSubFiles($path . '/' . $file, $files);
                }
            }
        } else if (is_file($path) && str_ends_with($path, '.php')) {
            $files[] = $path;
        }
    }

    private function readFile($path)
    {
        if (!file_exists($path)) {
            var_dump("file not found:" . $path);
            return [];
        }
        $file_arr = file($path);
        $result = ['use' => [], 'path' => $path];
        foreach ($file_arr as $line) {
            if (str_starts_with($line, 'namespace')) {
                $namespace = '';
                if (preg_match('/namespace ([a-zA-Z0-9\\\\]+);/i', $line, $namespace)) {
                    $result['namespace'] = $namespace[1];
                }
            } else if (str_starts_with($line, 'use')) {
                $use = '';
                if (preg_match('/use ([a-zA-Z0-9\\\\]+);/i', $line, $use)) {
                    $result['use'][] = $use[1];
                }
            } else if (str_starts_with($line, 'class')) {
                $class = '';
                if (preg_match('/class ([a-zA-Z0-9\\\\]+)/i', $line, $class)) {
                    $result['class'] = $class[1];
                }
                break;
            }
        }
        if (!isset($result['class'])) {
            printf($path . " is ignored.\r\n");
            return [];
        }
        $type = $this->getLayerType($result);
        $result['type'] = $type;
        $domain = $this->getDomainType($result);
        if ($domain) {
            $result['domain'] = $domain;
        }
        $result['use_format'] = $result['namespace'] . '\\' . $result['class'];
        return $result;
    }

    private function getLayerType(array $result)
    {
        if (str_ends_with($result['class'], 'Controller')) {
            return 'controller';
        } else if (str_ends_with($result['class'], 'DomainService')) {
            return 'domainService';
        } else if (str_ends_with($result['class'], 'Service')) {
            return 'service';
        } else if (str_contains($result['namespace'], 'Constants')) {
            return 'constant';
        } else if (str_contains($result['namespace'], 'Model') || str_contains($result['class'], 'Repository') || str_contains($result['class'], 'Collection') || str_contains($result['class'], 'Resource')) {
            return 'model';
        } else if (str_contains($result['class'], 'Listener') || str_contains($result['class'], 'Middleware')) {
            return 'middleware';
        } else if (str_contains($result['class'], 'Utils')) {
            return 'util';
        } else {
            echo("Unknown Layer Type:" . $result['class'] . '\r\n');
            return 'class';
        }
    }

    private function getDomainType(array $result)
    {
        if (preg_match("/domain/", $result['namespace'])) {
            $namespaces = explode('\\', $result['namespace']);
            for ($i = 0; $i < count($namespaces); $i++) {
                if ($namespaces[$i] == 'Domain' && $i < count($namespaces) - 1) {
                    return $namespaces[$i + 1];
                }
            }
            return null;
        } else {
            return null;
        }
    }
}
