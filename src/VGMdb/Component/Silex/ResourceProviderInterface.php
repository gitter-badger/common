<?php

namespace VGMdb\Component\Silex;

use Silex\Application as BaseApplication;

/**
 * Interface for resource providers.
 *
 * @author Gigablah <gigablah@vgmdb.net>
 */
interface ResourceProviderInterface
{
    /**
     * Loads a specific configuration.
     *
     * @param BaseApplication $app An application instance
     *
     * @return array
     */
    public function load(BaseApplication $app);

    /**
     * Checks if the provider is enabled.
     *
     * @return Boolean
     */
    public function isActive();

    /**
     * Checks if the provider should be autoloaded.
     *
     * @return Boolean
     */
    public function isAutoload();

    /**
     * Returns the provider name that this provider overrides.
     *
     * @return string The provider name it overrides or null if no parent
     */
    public function getParent();

    /**
     * Returns the provider name (the namespace segment).
     *
     * @return string The provider name
     */
    public function getName();

    /**
     * Gets the provider namespace.
     *
     * @return string The provider namespace
     */
    public function getNamespace();

    /**
     * Gets the provider directory path.
     *
     * The path should always be returned as a Unix path (with /).
     *
     * @return string The provider absolute path
     */
    public function getPath();
}
