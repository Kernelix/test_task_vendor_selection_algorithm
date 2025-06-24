<?php

namespace App\Tests\Service;

use App\Service\PurchasingPlanner;
use PHPUnit\Framework\TestCase;

class PurchasingPlannerTest extends TestCase
{
    private PurchasingPlanner $planner;

    protected function setUp(): void
    {
        $this->planner = new PurchasingPlanner();
    }

    public function testExample1(): void
    {
        $suppliers = [
            ['id' => 111, 'count' => 42, 'price' => 13, 'pack' => 1],
            ['id' => 222, 'count' => 77, 'price' => 11, 'pack' => 10],
            ['id' => 333, 'count' => 103, 'price' => 10, 'pack' => 50],
            ['id' => 444, 'count' => 65, 'price' => 12, 'pack' => 5]
        ];
        $n = 76;

        $result = $this->planner->findOptimalPlan($suppliers, $n);
        $this->assertPlanValid($suppliers, $n, $result);
        $this->assertPlanContainsSet($result, [
            ['id' => 111, 'qty' => 1],
            ['id' => 222, 'qty' => 20],
            ['id' => 333, 'qty' => 50],
            ['id' => 444, 'qty' => 5]
        ]);
    }

    public function testExample2(): void
    {
        $suppliers = [
            ['id' => 111, 'count' => 42, 'price' => 9, 'pack' => 1],
            ['id' => 222, 'count' => 77, 'price' => 11, 'pack' => 10],
            ['id' => 333, 'count' => 103, 'price' => 10, 'pack' => 50],
            ['id' => 444, 'count' => 65, 'price' => 12, 'pack' => 5]
        ];
        $n = 76;

        $result = $this->planner->findOptimalPlan($suppliers, $n);
        $this->assertPlanValid($suppliers, $n, $result);
        $this->assertPlanContainsSet($result, [
            ['id' => 111, 'qty' => 26],
            ['id' => 333, 'qty' => 50]
        ]);
    }

    public function testExample3(): void
    {
        $suppliers = [
            ['id' => 111, 'count' => 100, 'price' => 30, 'pack' => 1],
            ['id' => 222, 'count' => 60, 'price' => 11, 'pack' => 10],
            ['id' => 333, 'count' => 100, 'price' => 13, 'pack' => 50]
        ];
        $n = 76;

        $result = $this->planner->findOptimalPlan($suppliers, $n);
        $this->assertPlanValid($suppliers, $n, $result);
        $this->assertPlanContainsSet($result, [
            ['id' => 111, 'qty' => 6],
            ['id' => 222, 'qty' => 20],
            ['id' => 333, 'qty' => 50]
        ]);
    }

    public function testNoSolutionDueToTotalCount(): void
    {
        $suppliers = [
            ['id' => 111, 'count' => 10, 'price' => 10, 'pack' => 1],
            ['id' => 222, 'count' => 20, 'price' => 10, 'pack' => 1]
        ];
        $n = 50;

        $result = $this->planner->findOptimalPlan($suppliers, $n);
        $this->assertEmpty($result);
    }

    public function testNoSolutionDueToPackConstraints(): void
    {
        $suppliers = [
            ['id' => 111, 'count' => 100, 'price' => 10, 'pack' => 5],
            ['id' => 222, 'count' => 100, 'price' => 10, 'pack' => 5]
        ];
        $n = 13; // Невозможно собрать из шагов по 5

        $result = $this->planner->findOptimalPlan($suppliers, $n);
        $this->assertEmpty($result);
    }

    public function testZeroQuantity(): void
    {
        $suppliers = [
            ['id' => 111, 'count' => 100, 'price' => 10, 'pack' => 1]
        ];
        $n = 0;

        $result = $this->planner->findOptimalPlan($suppliers, $n);
        $this->assertEmpty($result);
    }

    public function testSingleSupplierExactMatch(): void
    {
        $suppliers = [
            ['id' => 111, 'count' => 50, 'price' => 10, 'pack' => 10]
        ];
        $n = 50;

        $result = $this->planner->findOptimalPlan($suppliers, $n);
        $this->assertEquals([['id' => 111, 'qty' => 50]], $result);
    }

    public function testPackLargerThanNeeded(): void
    {
        $suppliers = [
            ['id' => 111, 'count' => 100, 'price' => 10, 'pack' => 50],
            ['id' => 222, 'count' => 100, 'price' => 15, 'pack' => 30],
            ['id' => 333, 'count' => 100, 'price' => 5, 'pack' => 10]
        ];
        $n = 25;

        $result = $this->planner->findOptimalPlan($suppliers, $n);
        $this->assertEmpty($result, 'Для n=25 с заданными упаковками решение должно отсутствовать');
    }

    public function testMixedPacksWithCheapestOption(): void
    {
        $suppliers = [
            ['id' => 111, 'count' => 100, 'price' => 1, 'pack' => 1],
            ['id' => 222, 'count' => 100, 'price' => 2, 'pack' => 10],
            ['id' => 333, 'count' => 100, 'price' => 3, 'pack' => 50]
        ];
        $n = 35;

        $result = $this->planner->findOptimalPlan($suppliers, $n);

        // Должен быть только поставщик с pack=1 (дешевле)
        $this->assertEquals([['id' => 111, 'qty' => 35]], $result);
    }

    /**
     * Проверяет что план содержит указанный набор товаров (без учета порядка)
     */
    private function assertPlanContainsSet(array $plan, array $expectedSet): void
    {
        $this->assertSameSize($expectedSet, $plan, 'Несоответствие размера плана');

        $planIds = array_column($plan, 'id');
        $expectedIds = array_column($expectedSet, 'id');
        sort($planIds);
        sort($expectedIds);
        $this->assertEquals($expectedIds, $planIds, 'Несоответствие идентификаторов поставщиков');

        $planMap = array_column($plan, 'qty', 'id');
        foreach ($expectedSet as $expectedItem) {
            $this->assertEquals($expectedItem['qty'], $planMap[$expectedItem['id']],
                "Несоответствие количества для поставщика {$expectedItem['id']}");
        }
    }

    /**
     * Проверяет валидность плана закупки
     */
    private function assertPlanValid(array $suppliers, int $requiredQty, array $plan): void
    {
        // Проверяем общее количество
        $totalQty = array_sum(array_column($plan, 'qty'));
        $this->assertEquals($requiredQty, $totalQty, 'Несоответствие общего количества');

        // Проверяем ограничения поставщиков
        $supplierMap = array_column($suppliers, null, 'id');
        foreach ($plan as $item) {
            $supplier = $supplierMap[$item['id']];

            // Проверка количества
            $this->assertLessThanOrEqual(
                $supplier['count'],
                $item['qty'],
                "Закупленное количество превышает доступное у поставщика {$item['id']}"
            );

            // Проверка кратности
            $this->assertEquals(
                0,
                $item['qty'] % $supplier['pack'],
                "Количество не кратно упаковке для поставщика {$item['id']}"
            );
        }
    }
}