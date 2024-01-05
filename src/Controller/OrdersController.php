<?php

/**
 *  OrdersController.php
 */
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use App\Service\Interfaces\OrdersInterface;

/**
 * Class OrdersController
 * @package App\Controller
 */
class OrdersController extends AbstractController
{
    #[Route('/orders')]
    /**
     * @param OrdersInterface $orders
     * @return Response
     */
    public function index(OrdersInterface $orders): Response
    {
        return $this->json($orders->getOrders());
    }

    #[Route('/order/{id}', methods: ['GET', 'HEAD'])]

    /**
     * @param OrdersInterface $orders
     * @param int $id
     * @return Response
     */
    public function order(OrdersInterface $orders, int $id): Response
    {
        return $this->json($orders->getOrder($id));
    }
}
