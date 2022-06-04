<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Doctrine\Persistence\ManagerRegistry;

class LocaleSubscriber implements EventSubscriberInterface
{
    /**
     * Constructor.
     *
     * @param ManagerRegistry $doctrine
     * @param string $defaultLocale
     */
    public function __construct(ManagerRegistry $doctrine, private string $defaultLocale = 'en')
    {
        $this->doctrine = $doctrine;
        $this->defaultLocale = $defaultLocale;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        $config = $this->doctrine->getRepository('App\Entity\Config')->find(1);
        $lang = $config->getLanguage()->getCode();

        $request->getSession()->set('_locale', $lang);

        $request->setLocale($request->getSession()->get('_locale', $lang));
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // must be registered before (i.e. with a higher priority than) the default Locale listener
            KernelEvents::REQUEST => [['onKernelRequest', 20]],
        ];
    }
}
