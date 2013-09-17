<?php

namespace VGMdb\Component\View;

use Silex\Application;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

/**
 * Controller widgets are activated immediately after the main route, within the request scope.
 *
 * @author Gigablah <gigablah@vgmdb.net>
 */
abstract class ControllerWidget extends Widget
{
    /**
     * Create a new controller widget instance.
     *
     * @param Application   $app
     * @param ViewInterface $view
     * @return void
     */
    public function __construct(Application $app, ViewInterface $view)
    {
        $app['dispatcher']->addListener(KernelEvents::RESPONSE, array($this, 'onKernelResponse'), 0);
        parent::__construct($view);
    }

    /**
     * Intercepts responses and initializes the widget with data.
     *
     * @param GetResponseForControllerResultEvent $event The event to handle
     */
    abstract public function onKernelResponse(GetResponseForControllerResultEvent $event);
}
