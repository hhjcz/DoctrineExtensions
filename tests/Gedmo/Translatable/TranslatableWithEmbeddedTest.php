<?php

namespace Gedmo\Translatable;

use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityRepository;
use Tool\BaseTestCaseORM;
use Translatable\Fixture\Company;

class TranslatableWithEmbeddedTest extends BaseTestCaseORM
{
    const FIXTURE = 'Translatable\\Fixture\\Company';
    const TRANSLATION = 'Gedmo\\Translatable\\Entity\\Translation';

    private $translatableListener;

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager();
        $this->translatableListener = new TranslatableListener();
        $this->translatableListener->setTranslatableLocale('en_us');
        $this->translatableListener->setDefaultLocale('en_us');
        $evm->addEventSubscriber($this->translatableListener);

        $this->getMockSqliteEntityManager($evm);
    }

    /**
     * @test
     */
    public function testTranslate()
    {
        /** @var EntityRepository $repo */
        $repo = $this->em->getRepository(self::FIXTURE);

        $entity = new Company();
        $entity->setTitle('test');
        $entity->getLink()->setWebsite('website');
        $entity->getLink()->setFacebook('facebook');

        $this->em->persist($entity);
        $this->em->flush();

        $entity->setTranslatableLocale('de');
        $entity->setTitle('test-de');
        $entity->getLink()->setWebsite('website-de');
        $entity->getLink()->setFacebook('facebook-de');

        $this->em->persist($entity);
        $this->em->flush();
        $this->em->clear();

        /** @var Company $entity */
        $entity = $repo->findOneById($entity->getId());

        $repo = $this->em->getRepository(self::TRANSLATION);

        $translations = $repo->findTranslations($entity);

        $this->assertArrayHasKey('de', $translations);
        $this->assertSame('test-de', $translations['de']['title']);
        $this->assertSame('test', $entity->getTitle());

        $this->assertSame('website-de', $translations['de']['link.website']);
        $this->assertSame('website', $entity->getLink()->getWebsite());

        $this->assertSame('facebook-de', $translations['de']['link.facebook']);
        $this->assertSame('facebook', $entity->getLink()->getFacebook());

        $this->em->clear();

        $this->translatableListener->setTranslatableLocale('de');
        $repo = $this->em->getRepository(self::FIXTURE);
        $entity = $repo->findOneById($entity->getId());

        $this->assertSame('website-de', $entity->getLink()->getWebsite());
        $this->assertSame('facebook-de', $entity->getLink()->getFacebook());
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::FIXTURE,
            self::TRANSLATION,
        );
    }
}