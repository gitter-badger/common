<?php

namespace VGMdb\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Provides YAML or JSON configuration.
 *
 * @author Igor Wiedler <igor@wiedler.ch>
 */
class ConfigServiceProvider implements ServiceProviderInterface
{
    private $filename;
    private $replacements = array();

    public function register(Application $app)
    {
        $config = $this->readConfig();

        foreach ($config as $name => $value) {
            $app[$name] = $this->doReplacements($value);
        }
    }

    public function boot(Application $app)
    {
    }

    public function __construct($filename, array $replacements = array())
    {
        $this->filename = $filename;

        if ($replacements) {
            foreach ($replacements as $key => $value) {
                $this->replacements['%'.$key.'%'] = $value;
            }
        }
    }

    private function doReplacements($value)
    {
        if (!$this->replacements) {
            return $value;
        }

        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = $this->doReplacements($v);
            }

            return $value;
        }

        if (is_string($value)) {
            return strtr($value, $this->replacements);
        }

        return $value;
    }

    private function readConfig()
    {
        $format = $this->getFileFormat();

        if (!$this->filename || !$format) {
            throw new \RuntimeException('A valid configuration file must be passed before reading the config.');
        }

        if (!file_exists($this->filename)) {
            throw new \InvalidArgumentException(sprintf("The config file '%s' does not exist.", $this->filename));
        }

        if ('yaml' === $format) {
            if (!class_exists('Symfony\\Component\\Yaml\\Yaml')) {
                throw new \RuntimeException('Unable to read yaml as the Symfony Yaml Component is not installed.');
            }
            $config = Yaml::parse($this->filename);
            return ($config) ? $config : array();
        }

        if ('json' === $format) {
            $config = json_decode(file_get_contents($this->filename), true);
            return ($config) ? $config : array();
        }

        throw new \InvalidArgumentException(sprintf("The config file '%s' appears has invalid format '%s'.", $this->filename, $format));
    }

    public function getFileFormat()
    {
        $filename = $this->filename;

        if (preg_match('#.ya?ml(.dist)?$#i', $filename)) {
            return 'yaml';
        }

        if (preg_match('#.json(.dist)?$#i', $filename)) {
            return 'json';
        }

        return pathinfo($filename, PATHINFO_EXTENSION);
    }
}