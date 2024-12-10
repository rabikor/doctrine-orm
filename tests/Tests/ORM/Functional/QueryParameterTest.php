<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Types\Types;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

use function hex2bin;
use function sprintf;

#[Group('GH-11278')]
final class QueryParameterTest extends OrmFunctionalTestCase
{
    private int $userId;

    protected function setUp(): void
    {
        $this->useModelSet('cms');

        parent::setUp();

        $user             = new CmsUser();
        $user->reference  = hex2bin('1');
        $user->name       = 'John Doe';
        $user->username   = 'john';
        $user2            = new CmsUser();
        $user2->reference = hex2bin('2');
        $user2->name      = 'Jane Doe';
        $user2->username  = 'jane';
        $user3            = new CmsUser();
        $user3->reference = hex2bin('3');
        $user3->name      = 'Just Bill';
        $user3->username  = 'bill';

        $this->_em->persist($user);
        $this->_em->persist($user2);
        $this->_em->persist($user3);
        $this->_em->flush();

        $this->userId = $user->id;

        $this->_em->clear();
    }

    public function testParameterTypeInBuilder(): void
    {
        $result = $this->_em->createQueryBuilder()
            ->from(CmsUser::class, 'u')
            ->select('u.name')
            ->where('u.id = :id')
            ->setParameter('id', $this->userId, ParameterType::INTEGER)
            ->getQuery()
            ->getArrayResult();

        self::assertSame([['name' => 'John Doe']], $result);
    }

    public function testParameterTypeInQuery(): void
    {
        $result = $this->_em->createQueryBuilder()
            ->from(CmsUser::class, 'u')
            ->select('u.name')
            ->where('u.id = :id')
            ->getQuery()
            ->setParameter('id', $this->userId, ParameterType::INTEGER)
            ->getArrayResult();

        self::assertSame([['name' => 'John Doe']], $result);
    }

    public function testDbalTypeStringInBuilder(): void
    {
        $result = $this->_em->createQueryBuilder()
            ->from(CmsUser::class, 'u')
            ->select('u.name')
            ->where('u.id = :id')
            ->setParameter('id', $this->userId, Types::INTEGER)
            ->getQuery()
            ->getArrayResult();

        self::assertSame([['name' => 'John Doe']], $result);
    }

    public function testDbalTypeStringInQuery(): void
    {
        $result = $this->_em->createQueryBuilder()
            ->from(CmsUser::class, 'u')
            ->select('u.name')
            ->where('u.id = :id')
            ->getQuery()
            ->setParameter('id', $this->userId, Types::INTEGER)
            ->getArrayResult();

        self::assertSame([['name' => 'John Doe']], $result);
    }

    #[DataProvider('provideArrayParameters')]
    public function testArrayParameterTypeInQuery(string $field, ArrayParameterType $type, array $values): void
    {
        $result = $this->_em->createQueryBuilder()
            ->from(CmsUser::class, 'u')
            ->select('u.name')
            ->where(sprintf('u.%s IN (:values)', $field))
            ->orderBy(sprintf('u.%s', $field))
            ->setParameter('values', $values, $type)
            ->getQuery()
            ->getArrayResult();

        self::assertSame([['name' => 'Jane Doe'], ['name' => 'John Doe']], $result);
    }

    public static function provideArrayParameters(): array
    {
        return [
            'string' => ['username', ArrayParameterType::STRING, ['john', 'jane']],
            'binary' => ['reference', ArrayParameterType::BINARY, [hex2bin('1'), hex2bin('2')]],
            'integer' => ['id' => ArrayParameterType::INTEGER, [1, 2]],
        ];
    }
}
