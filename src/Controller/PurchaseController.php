<?php

namespace App\Controller;

use App\Service\PurchasingPlanner;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;



class PurchaseController extends AbstractController
{
    #[Route('/plan', name: 'purchase_plan', methods: ['POST'])]
    public function purchasePlan(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Валидация входных данных
        $constraint = new Assert\Collection([
            'fields' => [
                'N' => new Assert\Required([
                    new Assert\NotNull(['message' => 'Поле "N" обязательно для заполнения']),
                    new Assert\Type(['type' => 'integer', 'message' => 'Поле "N" должно быть целым числом']),
                    new Assert\GreaterThanOrEqual(['value' => 0, 'message' => 'Поле "N" должно быть положительным или нулевым']),
                    new Assert\LessThanOrEqual(['value' => 10000, 'message' => 'Поле "N" не может превышать 10000']),
                ]),
                'suppliers' => new Assert\Required([
                    new Assert\Count([
                        'max' => 1000,
                        'maxMessage' => 'Нельзя обработать более {{ limit }} поставщиков',
                    ]),
                    new Assert\All([
                        new Assert\Collection([
                            'fields' => [
                                'id' => new Assert\Required([
                                    new Assert\NotNull(['message' => 'Идентификатор поставщика обязателен']),
                                    new Assert\Type(['type' => 'integer', 'message' => 'Идентификатор поставщика должен быть целым числом']),
                                ]),
                                'count' => new Assert\Required([
                                    new Assert\NotNull(['message' => 'Количество товара обязательно']),
                                    new Assert\Type(['type' => 'integer', 'message' => 'Количество товара должно быть целым числом']),
                                    new Assert\Positive(['message' => 'Количество товара должно быть положительным']),
                                ]),
                                'price' => new Assert\Required([
                                    new Assert\NotNull(['message' => 'Цена товара обязательна']),
                                    new Assert\Type(['type' => 'numeric', 'message' => 'Цена товара должна быть числом']),
                                    new Assert\Positive(['message' => 'Цена товара должна быть положительной']),
                                ]),
                                'pack' => new Assert\Required([
                                    new Assert\NotNull(['message' => 'Кратность поставки обязательна']),
                                    new Assert\Type(['type' => 'integer', 'message' => 'Кратность поставки должна быть целым числом']),
                                    new Assert\Range([
                                        'min' => 1,
                                        'max' => 500,
                                        'notInRangeMessage' => 'Кратность поставки должна быть между {{ min }} и {{ max }}',
                                    ]),
                                ]),
                            ],
                            'allowMissingFields' => false,
                            'allowExtraFields' => false,
                        ]),
                    ]),
                ]),
            ],
            'allowMissingFields' => false,
            'allowExtraFields' => false,
        ]);

        $violations = $validator->validate($data, $constraint);

        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = [
                    'field' => $violation->getPropertyPath(),
                    'message' => $violation->getMessage()
                ];
            }
            return new JsonResponse(['errors' => $errors], 400);
        }

        $n = $data['N'];
        $suppliers = $data['suppliers'];

        if ($n === 0) {
            return new JsonResponse([]);
        }

        // Проверка уникальности ID поставщиков
        $ids = array_column($suppliers, 'id');
        if (count($ids) !== count(array_unique($ids))) {
            return new JsonResponse(['error' => 'Найдены дубликаты идентификаторов поставщиков'], 400);
        }

        $planner = new PurchasingPlanner();
        $plan = $planner->findOptimalPlan($suppliers, $n);

        return new JsonResponse($plan);
    }
}