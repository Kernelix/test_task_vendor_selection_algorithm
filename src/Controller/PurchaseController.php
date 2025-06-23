<?php

namespace App\Controller;

use App\Service\PurchasingPlanner;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class PurchaseController extends AbstractController
{
    #[Route('/plan', name: 'purchase_plan', methods: ['POST'])]
    public function purchasePlan(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $n = $data['N'] ?? 0;
        $suppliers = $data['suppliers'] ?? [];

        if ($n === 0) {
            return new JsonResponse([]);
        }

        $planner = new PurchasingPlanner();
        $plan = $planner->findOptimalPlan($suppliers, $n);

        return new JsonResponse($plan);
    }
}