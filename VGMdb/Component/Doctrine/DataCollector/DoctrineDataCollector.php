<?php

namespace VGMdb\Component\Doctrine\DataCollector;

use Silex\Application;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Logging\DebugStack;
use Doctrine\DBAL\Types\Type;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * DoctrineDataCollector.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class DoctrineDataCollector extends DataCollector
{
    private $app;
    private $registry;
    private $connections;
    private $managers;
    private $loggers = array();

    /*public function __construct(ManagerRegistry $registry = null)
    {
        $this->registry = $registry;
        $this->connections = (null !== $registry) ? $registry->getConnectionNames() : null;
        $this->managers = (null !== $registry) ? $registry->getManagerNames() : null;
    }*/

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->registry = $registry = isset($app['db.registry']) ? $app['db.registry'] : null;
        $this->connections = (null !== $registry) ? $registry->getConnectionNames() : null;
        $this->managers = (null !== $registry) ? $registry->getManagerNames() : null;
    }

    /**
     * Adds the stack logger for a connection.
     *
     * @param string     $name
     * @param DebugStack $logger
     */
    public function addLogger($name, DebugStack $logger)
    {
        $this->loggers[$name] = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $queries = array();
        foreach ($this->loggers as $name => $logger) {
            $queries[$name] = $this->sanitizeQueries($name, $logger->queries);
        }

        $this->data = array(
            'queries'     => $queries,
            'connections' => $this->connections,
            'managers'    => $this->managers,
        );
    }

    public function getManagers()
    {
        return $this->data['managers'];
    }

    public function getConnections()
    {
        return $this->data['connections'];
    }

    public function getQueryCount()
    {
        return array_sum(array_map('count', $this->data['queries']));
    }

    public function getQueries()
    {
        return $this->data['queries'];
    }

    public function getTime()
    {
        $time = 0;
        foreach ($this->data['queries'] as $queries) {
            foreach ($queries as $query) {
                $time += $query['executionMS'];
            }
        }

        return $time;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'db';
    }

    private function sanitizeQueries($connectionName, $queries)
    {
        foreach ($queries as $i => $query) {
            $queries[$i] = $this->sanitizeQuery($connectionName, $query);
        }

        return $queries;
    }

    private function sanitizeQuery($connectionName, $query)
    {
        $query['explainable'] = true;
        $query['params'] = (array) $query['params'];
        $query['executionMS'] = sprintf('%.3f', $query['executionMS']);
        foreach ($query['params'] as $j => &$param) {
            if (isset($query['types'][$j])) {
                // Transform the param according to the type
                $type = $query['types'][$j];
                if (is_string($type)) {
                    $type = Type::getType($type);
                }
                if ($type instanceof Type) {
                    $query['types'][$j] = $type->getBindingType();
                    $param = get_class($type);
                    if (null !== $this->registry) {
                        $platform = $this->registry->getConnection($connectionName)->getDatabasePlatform();
                    } else {
                        $dbs = $this->app['dbs'];
                        $platform = $dbs[$connectionName]->getDatabasePlatform();
                    }
                    $param = $type->convertToDatabaseValue($param, $platform);
                }
            }

            list($param, $explainable) = $this->sanitizeParam($param);
            if (!$explainable) {
                $query['explainable'] = false;
            }
        }

        return $query;
    }

    /**
     * Sanitizes a param.
     *
     * The return value is an array with the sanitized value and a boolean
     * indicating if the original value was kept (allowing to use the sanitized
     * value to explain the query).
     *
     * @param mixed $var
     *
     * @return array
     */
    private function sanitizeParam($var)
    {
        if (is_object($var)) {
            return array(sprintf('Object(%s)', get_class($var)), false);
        }

        if (is_array($var)) {
            $a = array();
            $original = true;
            foreach ($var as $k => $v) {
                list($value, $orig) = $this->sanitizeParam($v);
                $original = $original && $orig;
                $a[$k] = $value;
            }

            return array($a, $original);
        }

        if (is_resource($var)) {
            return array(sprintf('Resource(%s)', get_resource_type($var)), false);
        }

        return array($var, true);
    }
}