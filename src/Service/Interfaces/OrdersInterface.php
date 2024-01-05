<?php

namespace App\Service\Interfaces;

interface OrdersInterface
{
    CONST DISCOUNT_TYPE_DOLLAR = 'DOLLAR';
    CONST DISCOUNT_TYPE_PERCENT = 'PERCENT';
    public function getOrders(): array;
    public function getOrder(int $orderId): array;
}
