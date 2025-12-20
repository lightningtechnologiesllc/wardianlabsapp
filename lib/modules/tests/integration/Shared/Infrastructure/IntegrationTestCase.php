<?php

declare(strict_types=1);

namespace Tests\Integration\App\Shared\Infrastructure;

use App\Kernel;
//use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class IntegrationTestCase extends KernelTestCase
{
    protected function setUp(): void
    {
        $_SERVER['KERNEL_CLASS'] = $this->kernelClass();

        self::bootKernel(['environment' => 'test']);

        parent::setUp();
    }

    protected function kernelClass(): string
    {
        return Kernel::class;
    }

//    protected function clearUnitOfWork(): void
//    {
//        $this->service(EntityManager::class)->clear();
//    }
//
//    protected function dropCollection(string $documentClass): void
//    {
//        $this->service(EntityManager::class)->getDocumentCollection($documentClass)->drop();
//    }

    protected function service(string $className, ?string $id = null): object
    {
        // @phpstan-ignore-next-line
        return self::getContainer()->get($id ?? $className);
    }

    /** @return mixed */
    protected function parameter($parameter)
    {
        return self::getContainer()->getParameter($parameter);
    }
}
