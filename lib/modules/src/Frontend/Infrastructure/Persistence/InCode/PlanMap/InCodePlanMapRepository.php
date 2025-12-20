<?php
declare(strict_types=1);

namespace App\Frontend\Infrastructure\Persistence\InCode\PlanMap;

use App\Frontend\Domain\PlanMap;
use App\Frontend\Domain\PlanMapRepository;
use Symfony\Component\Uid\Uuid;

final class InCodePlanMapRepository implements PlanMapRepository
{
    const TECHABREATH_TENANT_ID = "01988724-a776-7921-be94-0dd8535cc470";
    const MONGEMALO_TENANT_ID = "01988725-0082-78b3-8d74-b154397078a6";

    private array $planMap;

    public function __construct()
    {
        $this->planMap = [
            self::TECHABREATH_TENANT_ID => [
                "price_1RoVMePOQ7ui3NRxAQv5Jtpc" => '1398028474089738423',
            ],
            self::MONGEMALO_TENANT_ID => [
                // Yonki de oro, Mentoría
                'price_1Lj60PLzEFdh6KXiM1tB3D6c' => '1395730725960945756',
                'price_1OTYA6LzEFdh6KXiL1jzLGU0' => '1395730725960945756',
                'mentoria-semestral' => '1395730725960945756',
                'price_1NbOatLzEFdh6KXin7CcFlfq' => '1395730725960945756',
                'price_1QcqO9LzEFdh6KXiZWrA67Ft' => '1395730725960945756',

                // Yonki de plata, Grupo Alfa
                'price_1RkqDRLzEFdh6KXi66Siv6RC' => '1398261317353082990',
                'price_1RjL12LzEFdh6KXiQk70IJA9' => '1398261317353082990',
                'price_1NbVKzLzEFdh6KXifN3J3Nce' => '1398261317353082990',
            ],
        ];
    }

    public function findByTenantId(Uuid $tenantId): ?PlanMap
    {
        if (isset($this->planMap[$tenantId->toString()])) {
            return new PlanMap($this->planMap[$tenantId->toString()]);
        }

        return null;
    }
}
