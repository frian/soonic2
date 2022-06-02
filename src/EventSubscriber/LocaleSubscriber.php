<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use App\Entity\Config;

class LocaleSubscriber implements EventSubscriberInterface
{
    private $defaultLocale;

    /**
	 * Entity Manager
	 *
	 * @var EntityManager $em
	 */
	protected $em;

	/**
	 * Constructor
	 *
	 * @param EntityManager $em
	 */
	public function __construct($defaultLocale = 'en', \Doctrine\ORM\EntityManager $em) {
		$this->em = $em;
		$this->defaultLocale = $defaultLocale;
	}



    public function onKernelRequest(RequestEvent $event)
    {
        $request = $event->getRequest();

        $config = $this->em->getRepository('App\Entity\Config')->find(1);
        $lang = $config->getLanguage()->getCode();

        $request->getSession()->set('_locale', $lang);

        $request->setLocale($request->getSession()->get('_locale', $lang));
    }

    public static function getSubscribedEvents()
    {
        return [
            // must be registered before (i.e. with a higher priority than) the default Locale listener
            KernelEvents::REQUEST => [['onKernelRequest', 20]],
        ];
    }
}
