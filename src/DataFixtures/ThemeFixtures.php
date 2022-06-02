<?php

namespace App\DataFixtures;

use App\Entity\Theme;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ThemeFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $themes = [
            0 => [
                'name' => 'default-dark',
            ],
            1 => [
                'name' => 'default-clear',
            ],
        ];

        /*
    	 * Add themes
    	 */
        foreach ($themes as $index => $themeData) {
            // create theme
            $theme = new Theme();
            $theme->setName($themeData['name']);

            // add reference for further fixtures
            $this->addReference('theme'.$index, $theme);

            $manager->persist($theme);
            $manager->flush();
        }
    }
}
