<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Language;

class LanguageFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $languages = array(
    		0 => array(
    			'name' => 'english',
                'code' => 'en'
    		),
    		1 => array(
    			'name' => 'français',
                'code' => 'fr'
    		),
            2 => array(
    			'name' => 'italiano',
                'code' => 'it'
    		),
    	);

    	/**
    	 * Add languages
    	 */
    	foreach ( $languages as $index => $languageData ) {

	    	// create language
	        $language = new Language();
	        $language->setName($languageData['name']);
            $language->setCode($languageData['code']);

	        // add reference for further fixtures
	        $this->addReference('language'.$index, $language);

	    	$manager->persist($language);
	    	$manager->flush();
    	}
    }
}
