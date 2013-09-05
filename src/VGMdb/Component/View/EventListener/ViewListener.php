<?php

namespace VGMdb\Component\View\EventListener;

use VGMdb\Application;
use VGMdb\Component\HttpFoundation\Response;
use VGMdb\Component\View\ViewInterface;
use VGMdb\Component\Translation\Routing\TranslationRouteLoader;
use Symfony\Component\HttpFoundation\Response as BaseResponse;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Wraps and renders views for HTML responses.
 *
 * @author Gigablah <gigablah@vgmdb.net>
 */
class ViewListener implements EventSubscriberInterface
{
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }

        if ($event->getRequest()->getRequestFormat() !== 'html' && $event->getRequest()->getRequestFormat() !== 'pdf') {
            return;
        }

        $result = $event->getControllerResult();
        if ($result instanceof BaseResponse) {
            return;
        }

        if (!$result instanceof ViewInterface) {
            if (null === $view = $event->getRequest()->attributes->get('_view')) {
                $route = $event->getRequest()->attributes->get(
                    '_original_route',
                    $event->getRequest()->attributes->get('_route')
                );

                // automatically generate the template path
                if ($provider = $event->getRequest()->attributes->get('_provider')) {
                    $view = sprintf('%s:%s', $provider, $route);
                } else {
                    $view = $route;
                }
            }

            $result = $this->app['view']($view, $result);
        }

        $event->setControllerResult($result);
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }

        if ($event->getRequest()->getRequestFormat() !== 'html') {
            return;
        }

        $view = $event->getResponse()->getContent();
        if (!$view instanceof ViewInterface) {
            return;
        }

        $content = (string) $view;

        if ($view->hasException()) {
            $view['_error'] = $view->getException()->getMessage();
            $content = '<pre>'.json_encode($view->getArrayCopy(), JSON_PRETTY_PRINT).'</pre>';
            //throw $view->getException();
        }

        $event->getResponse()->setContent($content);
    }

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::VIEW => array('onKernelView', -16),
            KernelEvents::RESPONSE => array('onKernelResponse', -64),
        );
    }
}
