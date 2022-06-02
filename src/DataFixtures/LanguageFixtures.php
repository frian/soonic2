<?php

namespace App\DataFixtures;

use App\Entity\Language;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class LanguageFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $languages = [
            0 => [
                'name' => 'english',
                'code' => 'en',
            ],
            1 => [
                'name' => 'français',
                'code' => 'fr',
            ],
            2 => [
                'name' => 'italiano',
                'code' => 'it',
            ],
        ];

        /*
    	 * Add languages
    	 */
        foreach ($languages as $index => $languageData) {
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
