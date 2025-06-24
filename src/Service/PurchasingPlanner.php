<?php

declare(strict_types=1);

namespace App\Service;

class PurchasingPlanner
{
    public function findOptimalPlan(array $suppliers, int $n): array
    {
        if ($n === 0) {
            return [];
        }

        $groups = $this->groupSuppliersByPack($suppliers);
        $groupData = $this->preprocessGroups($groups, $n);

        [$dp, $choices] = $this->runDynamicProgramming($groupData, $n);

        if ($dp[$n] === INF) {
            return [];
        }

        return $this->reconstructPlan($groupData, $choices, $n);
    }

    private function groupSuppliersByPack(array $suppliers): array
    {
        $groups = [];
        foreach ($suppliers as $supplier) {
            $pack = $supplier['pack'];
            $groups[$pack][] = $supplier;
        }
        return $groups;
    }

    private function preprocessGroups(array $groups, int $n): array
    {
        $groupData = [];
        foreach ($groups as $pack => $group) {
            $suppliersInGroup = $this->prepareSuppliers($group, $pack);
            usort($suppliersInGroup, fn ($a, $b) => $a['c'] <=> $b['c']);

            [$prefixQty, $prefixCost, $totalQty] = $this->calculatePrefixSums($suppliersInGroup);
            $tMax = min($totalQty, (int)($n / $pack));
            $costGroup = $this->buildCostGroup($prefixQty, $prefixCost, $suppliersInGroup, $tMax, $totalQty);

            $groupData[] = [
                'pack' => $pack,
                'costGroup' => $costGroup,
                'suppliers' => $suppliersInGroup,
                'tMax' => $tMax,
            ];
        }
        return $groupData;
    }

    private function prepareSuppliers(array $group, int $pack): array
    {
        return array_map(function ($s) use ($pack) {
            $m = (int)($s['count'] / $pack);
            $c = $pack * $s['price'];
            return $s + ['m' => $m, 'c' => $c];
        }, $group);
    }

    private function calculatePrefixSums(array $suppliers): array
    {
        $prefixQty = [0];
        $prefixCost = [0];
        $totalQty = 0;
        $totalCost = 0;

        foreach ($suppliers as $s) {
            $totalQty += $s['m'];
            $totalCost += $s['m'] * $s['c'];
            $prefixQty[] = $totalQty;
            $prefixCost[] = $totalCost;
        }

        return [$prefixQty, $prefixCost, $totalQty];
    }

    private function buildCostGroup(array $prefixQty, array $prefixCost, array $suppliers, int $tMax, int $totalQty): array
    {
        $costGroup = array_fill(0, $tMax + 1, INF);
        $costGroup[0] = 0;

        for ($k = 1; $k <= $tMax; $k++) {
            if ($k > $totalQty) {
                break;
            }

            $idx = $this->binarySearch($prefixQty, $k);
            $supplierIdx = min($idx, count($suppliers) - 1);

            // Вычисляем остаток партий для покупки
            $remaining = $k - $prefixQty[$idx];

            // Если есть остаток, добавляем его стоимость
            if ($remaining > 0) {
                $costGroup[$k] = $prefixCost[$idx] + $remaining * $suppliers[$supplierIdx]['c'];
            } else {
                // Берем полностью из префиксной суммы
                $costGroup[$k] = $prefixCost[$idx];
            }
        }

        return $costGroup;
    }

    private function binarySearch(array $arr, int $target): int
    {
        $left = 0;
        $right = count($arr) - 1;
        $result = 0;

        while ($left <= $right) {
            $mid = (int)(($left + $right) / 2);
            if ($arr[$mid] <= $target) {
                $result = $mid;
                $left = $mid + 1;
            } else {
                $right = $mid - 1;
            }
        }
        return $result;
    }

    private function runDynamicProgramming(array $groupData, int $n): array
    {
        $dp = array_fill(0, $n + 1, INF);
        $dp[0] = 0;
        $choices = [];

        foreach ($groupData as $group) {
            $newDp = $dp;
            $newChoice = array_fill(0, $n + 1, -1);
            $pack = $group['pack'];
            $costGroup = $group['costGroup'];
            $tMaxGroup = $group['tMax'];

            for ($r = 0; $r < $pack; $r++) {
                $arr_r = [];
                for ($x = $r; $x <= $n; $x += $pack) {
                    $s = (int)(($x - $r) / $pack);
                    $arr_r[$s] = $dp[$x];
                }
                $sMax = count($arr_r) - 1;

                $new_arr_r = array_fill(0, $sMax + 1, INF);
                $new_choice_r = array_fill(0, $sMax + 1, -1);

                for ($s = 0; $s <= $sMax; $s++) {
                    $kMax = min($s, $tMaxGroup);
                    for ($k = 0; $k <= $kMax; $k++) {
                        $j = $s - $k;
                        if ($j < 0 || $arr_r[$j] === INF) {
                            continue;
                        }
                        $cost = $arr_r[$j] + $costGroup[$k];
                        if ($cost < $new_arr_r[$s]) {
                            $new_arr_r[$s] = $cost;
                            $new_choice_r[$s] = $k;
                        }
                    }
                }

                for ($s = 0; $s <= $sMax; $s++) {
                    $x = $r + $s * $pack;
                    if ($x <= $n) {
                        $newDp[$x] = $new_arr_r[$s];
                        $newChoice[$x] = $new_choice_r[$s];
                    }
                }
            }

            $dp = $newDp;
            $choices[] = $newChoice;
        }

        return [$dp, $choices];
    }

    private function reconstructPlan(array $groupData, array $choices, int $n): array
    {
        $result = [];
        $x = $n;

        for ($i = count($groupData) - 1; $i >= 0; $i--) {
            $group = $groupData[$i];
            $choice = $choices[$i];
            $k = $choice[$x] ?? -1;

            if ($k <= 0) {
                continue;
            }

            $remaining = $k;
            foreach ($group['suppliers'] as $s) {
                if ($remaining <= 0) {
                    break;
                }
                $take = min($remaining, $s['m']);
                if ($take > 0) {
                    $result[] = ['id' => $s['id'], 'qty' => $take * $s['pack']];
                    $remaining -= $take;
                }
            }
            $x -= $k * $group['pack'];
        }

        return $result;
    }
}
