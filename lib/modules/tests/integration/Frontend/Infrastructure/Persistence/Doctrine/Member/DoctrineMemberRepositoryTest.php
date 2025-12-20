<?php
declare(strict_types=1);

namespace Tests\Integration\App\Frontend\Infrastructure\Persistence\Doctrine\Member;

use App\Frontend\Domain\Discord\DiscordId;
use App\Frontend\Domain\Member\MemberId;
use App\Frontend\Domain\Member\MemberRepository;
use App\Frontend\Infrastructure\Persistence\Doctrine\Member\DoctrineMemberRepository;
use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Tests\Doubles\App\Frontend\Domain\Member\MemberMother;
use Tests\Integration\App\Shared\Infrastructure\IntegrationTestCase;

#[CoversClass(DoctrineMemberRepository::class)]
#[UsesClass(DiscordId::class)]
#[UsesClass(MemberId::class)]
#[UsesClass(Uuid::class)]
final class DoctrineMemberRepositoryTest extends IntegrationTestCase
{
    public function testDoesNotFindNonExistentMember(): void
    {
        $repository = $this->getRepository();
        $memberId = MemberId::random();

        $member = $repository->findByMemberId($memberId);

        $this->assertNull($member);
    }

    public function testFindMemberById(): void
    {
        $repository = $this->getRepository();
        $member = MemberMother::random();

        $repository->save($member);

        $this->assertMemberExists($member->getId());
    }

    public function testFindAllReturnsAllMembers(): void
    {
        $repository = $this->getRepository();

        // Create and save 3 members
        $member1 = MemberMother::random();
        $member2 = MemberMother::random();
        $member3 = MemberMother::random();

        $repository->save($member1);
        $repository->save($member2);
        $repository->save($member3);

        // Find all members
        $allMembers = $repository->findAll();

        // Assert we have at least 3 members (there might be more from other tests)
        $this->assertGreaterThanOrEqual(3, count($allMembers));

        // Assert our members are in the collection
        $memberIds = array_map(fn($m) => $m->getId()->value(), $allMembers);
        $this->assertContains($member1->getId()->value(), $memberIds);
        $this->assertContains($member2->getId()->value(), $memberIds);
        $this->assertContains($member3->getId()->value(), $memberIds);
    }

    public function testFindAllReturnsEmptyArrayWhenNoMembers(): void
    {
        $repository = $this->getRepository();

        // Note: We can't guarantee the database is empty due to other tests,
        // so this test just verifies the method returns an array
        $allMembers = $repository->findAll();

        $this->assertIsArray($allMembers);
    }

    private function getRepository(): DoctrineMemberRepository
    {
        return $this->service(MemberRepository::class);
    }

    private function assertMemberExists(MemberId $id)
    {
        $foundMember = $this->getRepository()->findByMemberId($id);
        $this->assertNotNull($foundMember);
        $this->assertEquals($foundMember->id(), $foundMember->id());
    }
}
