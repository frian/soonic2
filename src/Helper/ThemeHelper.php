<?php

namespace App\Helper;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Security;

/**
 * A helper service for theme changing.
 */
class ThemeHelper
{
    /**
     * Constructor.
     *
     * @param ManagerRegistry $doctrine
     * @param Security $security
     */
    public function __construct(ManagerRegistry $doctrine, Security $security)
    {
        $this->doctrine = $doctrine;
        $this->security = $security;
    }

    /**
     * get user theme.
     *
     * @return string $theme
     */
    public function get(): string
    {
        $config = $this->doctrine->getRepository('App\Entity\Config')->find(1);
        $theme = $config->getTheme();

        return $theme;
    }
}
