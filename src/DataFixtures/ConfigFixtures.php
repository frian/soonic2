<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Config;

class ConfigFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $config = new Config();
        $config->setLanguage($this->getReference('language1'));
        $config->setTheme($this->getReference('theme0'));

        // add reference for further fixtures
        $this->addReference('config', $config);

    	$manager->persist($config);
    	$manager->flush();
    }

    public function getDependencies()
    {
        return array(
            LanguageFixtures::class,
            ThemeFixtures::class
        );
    }
}
