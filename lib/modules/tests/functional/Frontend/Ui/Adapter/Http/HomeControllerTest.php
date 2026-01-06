<?php
declare(strict_types=1);

namespace Tests\Functional\App\Frontend\Ui\Adapter\Http;

use App\Admin\Domain\Tenant\TenantRepository;
use App\Admin\Domain\User\UserRepository;
use App\Frontend\Ui\Adapter\Http\HomeController;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Tests\Doubles\App\Admin\Domain\Tenant\TenantMother;
use Tests\Doubles\App\Admin\Domain\User\UserMother;

#[CoversClass(HomeController::class)]
final class HomeControllerTest extends WebTestCase
{
    public function testRedirectsToAdminLoginWhenHostIsMainDomain(): void
    {
        $client = static::createClient();
        $mainDomain = static::getContainer()->getParameter('main_domain');

        $client->request('GET', '/', [], [], ['HTTP_HOST' => $mainDomain]);

        $this->assertResponseRedirects('/admin/login');
    }

    public function testReturns404WhenHostIsSubdomainAndTenantNotFound(): void
    {
        $client = static::createClient();
        $mainDomain = static::getContainer()->getParameter('main_domain');

        $client->request('GET', '/', [], [], ['HTTP_HOST' => 'unknown-tenant.' . $mainDomain]);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testReturns200WhenTenantExists(): void
    {
        $client = static::createClient();
        $mainDomain = static::getContainer()->getParameter('main_domain');

        $userRepository = static::getContainer()->get(UserRepository::class);
        $tenantRepository = static::getContainer()->get(TenantRepository::class);

        $owner = UserMother::random();
        $userRepository->save($owner);

        $tenant = TenantMother::randomWithOwner($owner);
        $tenantRepository->save($tenant);

        $client->request('GET', '/', [], [], ['HTTP_HOST' => $tenant->getSubdomain() . '.' . $mainDomain]);

        $this->assertResponseStatusCodeSame(200);
    }
}
