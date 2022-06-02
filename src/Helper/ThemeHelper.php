<?php

namespace App\Helper;

/**
 * class representing a weekly calendar
 */
class ThemeHelper {

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
    public function __construct(\Doctrine\ORM\EntityManager $em, $securityContext) {

 		$this->em = $em;
 		$this->context = $securityContext;
 	}

    /**
     * get user theme
     *
     * @return string $theme
     */
    public function get() {

        $config = $this->em->getRepository('App\Entity\Config')->find(1);
        $theme = $config->getTheme();

        return $theme;
    }
}
