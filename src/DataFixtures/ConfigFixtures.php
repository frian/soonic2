<?php

namespace App\DataFixtures;

use App\Entity\Config;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

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
        return [
            LanguageFixtures::class,
            ThemeFixtures::class,
        ];
    }
}
