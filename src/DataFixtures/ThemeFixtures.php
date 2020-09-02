<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Theme;

class ThemeFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $themes = array(
    		0 => array(
    			'name' => 'default-dark',
    		),
            1 => array(
    			'name' => 'default-clear',
    		),
    	);

    	/**
    	 * Add themes
    	 */
    	foreach ( $themes as $index => $themeData ) {

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
