<?php
declare(strict_types=1);

namespace App\Admin\Infrastructure\Persistence\Doctrine\User;

use App\Admin\Domain\User\PlatformSubscription;
use App\Admin\Domain\User\User;
use App\Admin\Domain\User\UserId;
use App\Admin\Domain\User\UserRepository;
use App\Admin\Infrastructure\Persistence\Doctrine\Tenant\DoctrineTenant;
use App\Frontend\Domain\Discord\DiscordId;
use App\Shared\Infrastructure\Persistence\Doctrine\DoctrineRepository;

class DoctrineUserRepository extends DoctrineRepository implements UserRepository
{
    public function findOneByDiscordId(DiscordId $discordId): ?User
    {
        $this->entityManager()->clear();
        $user = $this->getRepository()->findOneBy(['discordUserId' => $discordId->value()]);

        if (null === $user) {
            return null;
        }

        return $this->toDomain($user);
    }

    public function findByUserId(UserId $userId): ?User
    {
        $this->entityManager()->clear();
        $user = $this->getRepository()->findOneBy(['userId' => $userId->value()]);

        if (null === $user) {
            return null;
        }

        return $this->toDomain($user);
    }

    public function findByEmail(string $email): ?User
    {
        $this->entityManager()->clear();
        $user = $this->getRepository()->findOneBy(['email' => $email]);

        if (null === $user) {
            return null;
        }

        return $this->toDomain($user);
    }

    public function findByUsername(string $username): ?User
    {
        $this->entityManager()->clear();
        $user = $this->getRepository()->findOneBy(['username' => $username]);

        if (null === $user) {
            return null;
        }

        return $this->toDomain($user);
    }

    public function findBySubscriptionId(string $subscriptionId): ?User
    {
        $this->entityManager()->clear();

        // Note: This requires platform subscription columns to exist in DoctrineUser
        // For now, we'll search all users and filter in PHP
        $users = $this->getRepository()->findAll();

        foreach ($users as $doctrineUser) {
            $domainUser = $this->toDomain($doctrineUser);
            if ($domainUser->getPlatformSubscription()?->getSubscriptionId() === $subscriptionId) {
                return $domainUser;
            }
        }

        return null;
    }

    public function save(User $user): void
    {
        $this->persist($this->toDoctrine($user));
    }

    private function toDoctrine(User $user): DoctrineUser
    {
        $this->entityManager()->clear();
        $doctrineUser = $this->getRepository()->findOneBy(['discordUserId' => $user->getDiscordId()->value()]);

        $subscription = $user->getPlatformSubscription();

        if (null === $doctrineUser) {
            return new DoctrineUser(
                userId: $user->id()->value(),
                discordUserId: $user->getDiscordId()->value(),
                username: $user->getUsername(),
                globalName: $user->getGlobalName(),
                email: $user->getEmail(),
                avatar: $user->getAvatar(),
                accessToken: $user->getAccessToken()->accessToken,
                refreshToken: $user->getAccessToken()->refreshToken,
                expiresOn: $user->getAccessToken()->expiresOn,
                scope: $user->getAccessToken()->scope,
                tokenType: $user->getAccessToken()->tokenType,
                platformSubscriptionId: $subscription?->getSubscriptionId(),
                platformPlanId: $subscription?->getPlanId(),
                platformSubscriptionStatus: $subscription?->getStatus(),
                platformSubscriptionExpiresAt: $subscription?->getExpiresAt(),
            );
        }

        $doctrineUser->discordUserId = $user->getDiscordId()->value();
        $doctrineUser->username = $user->getUsername();
        $doctrineUser->globalName = $user->getGlobalName();
        $doctrineUser->email = $user->getEmail();
        $doctrineUser->avatar = $user->getAvatar();
        $doctrineUser->accessToken = $user->getAccessToken()->accessToken;
        $doctrineUser->refreshToken = $user->getAccessToken()->refreshToken;
        $doctrineUser->expiresOn = $user->getAccessToken()->expiresOn;
        $doctrineUser->scope = $user->getAccessToken()->scope;
        $doctrineUser->tokenType = $user->getAccessToken()->tokenType;
        $doctrineUser->platformSubscriptionId = $subscription?->getSubscriptionId();
        $doctrineUser->platformPlanId = $subscription?->getPlanId();
        $doctrineUser->platformSubscriptionStatus = $subscription?->getStatus();
        $doctrineUser->platformSubscriptionExpiresAt = $subscription?->getExpiresAt();

        return $doctrineUser;
    }

    public function toDomain(DoctrineUser $doctrineUser): User
    {
        $platformSubscription = null;
        if ($doctrineUser->platformSubscriptionId !== null) {
            $platformSubscription = new PlatformSubscription(
                subscriptionId: $doctrineUser->platformSubscriptionId,
                planId: $doctrineUser->platformPlanId,
                status: $doctrineUser->platformSubscriptionStatus,
                expiresAt: $doctrineUser->platformSubscriptionExpiresAt,
            );
        }

        $user = new User(
            userId: new UserId($doctrineUser->userId),
            discordId: new DiscordId($doctrineUser->discordUserId),
            username: $doctrineUser->username,
            globalName: $doctrineUser->globalName,
            email: $doctrineUser->email,
            avatar: $doctrineUser->avatar,
            accessToken: new \App\Shared\Domain\Discord\DiscordAccessToken(
                accessToken: $doctrineUser->accessToken,
                refreshToken: $doctrineUser->refreshToken,
                expiresOn: $doctrineUser->expiresOn,
                scope: $doctrineUser->scope,
                tokenType: $doctrineUser->tokenType,
            ),
            platformSubscription: $platformSubscription,
        );
        /** @var DoctrineTenant $doctrineTenant */
        foreach ($doctrineUser->tenants->toArray() as $doctrineTenant) {
            $user->addTenant($doctrineTenant->toDomain());
        }

        return $user;
    }

    private function getRepository(): \Doctrine\ORM\EntityRepository
    {
        return $this->repository(DoctrineUser::class);
    }
}
