<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Repository;

use Shlinkio\Shlink\Core\Entity\Tag;
use Shlinkio\Shlink\Core\Repository\TagRepository;
use Shlinkio\Shlink\TestUtils\DbTest\DatabaseTestCase;

class TagRepositoryTest extends DatabaseTestCase
{
    protected const ENTITIES_TO_EMPTY = [
        Tag::class,
    ];

    /** @var TagRepository */
    private $repo;

    protected function setUp(): void
    {
        $this->repo = $this->getEntityManager()->getRepository(Tag::class);
    }

    /** @test */
    public function deleteByNameDoesNothingWhenEmptyListIsProvided()
    {
        $this->assertEquals(0, $this->repo->deleteByName([]));
    }

    /** @test */
    public function allTagsWhichMatchNameAreDeleted()
    {
        $names = ['foo', 'bar', 'baz'];
        $toDelete = ['foo', 'baz'];

        foreach ($names as $name) {
            $this->getEntityManager()->persist(new Tag($name));
        }
        $this->getEntityManager()->flush();

        $this->assertEquals(2, $this->repo->deleteByName($toDelete));
    }
}
