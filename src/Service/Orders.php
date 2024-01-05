<?php

namespace App\Service;

use App\Service\Interfaces\OrdersInterface;
use App\Service\Interfaces\ParseJsonLinesInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Carbon\Carbon;

class Orders implements OrdersInterface
{
    private ParseJsonLinesInterface $parseJsonLines;
    private ContainerBagInterface $containerBag;
    private LoggerInterface $logger;

    public function __construct(
        ParseJsonLinesInterface $parseJsonLines,
        ContainerBagInterface $containerBag,
        LoggerInterface $logger
    ) {
        $this->parseJsonLines = $parseJsonLines;
        $this->containerBag = $containerBag;
        $this->logger = $logger;
    }

    public function getOrders(): array
    {
        return $this->parseJsonLines->delineFromFile($this->getOrdersJsonLineFile());
    }

    public function getOrder(int $orderId): array
    {
        try {
            $orders = $this->getOrders();
            if (isset($orders['error'])) {
                return $orders['error'];
            }

            return $this->formatOrderDetails(
                array_filter(
                    $orders, function ($orderItem) use ($orderId) {
                        return (int) $orderItem['order_id'] === $orderId;
                    }
                )
            );
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());

            return [
                'error' => [
                    'code' => $exception->getCode(),
                    'message' => $exception->getMessage(),
                ]
            ];
        }
    }

    private function formatOrderDetails(array $orderDetail): array
    {

        try {
            $orderDetail = current($orderDetail);
            $totalOrderValue = $this->calculateTotalOrderValue($orderDetail);
            $averageUnitPrice = array_sum(array_column($orderDetail['items'], 'unit_price'))
                / count($orderDetail['items']);

            return [
                'order_id' => $orderDetail['order_id'],
                'order_date' => Carbon::parse($orderDetail['order_datetime'])->format('d/m/Y'),
                'total_order_value' => round($totalOrderValue, 2),
                'average_unit_price' => round($averageUnitPrice, 2),
                'unit_count' => array_sum(array_column($orderDetail['items'], 'quantity')),
                'customer_state' => $orderDetail['customer']['shipping_address']['state']
            ];
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            throw new NotFoundHttpException('Order Not found');
        }
    }

    private function getOrdersJsonLineFile(): string
    {
        return $this->containerBag->get('orders_jsonlines');
    }

    private function calculateTotalOrderValue(mixed $orderDetail): mixed
    {
        if (!$orderDetail['items']) {
             throw new NotFoundHttpException('Order Not found');
        }

        $totalOrderValues = array_map(
            function ($item) {
                return $item['quantity'] * $item['unit_price'];
            }, $orderDetail['items']
        );

        $totalOrderValue = array_sum($totalOrderValues);
        if (count($orderDetail['discounts']) <= 0) {
            return $totalOrderValue;
        }

        if (self::DISCOUNT_TYPE_PERCENT !== current($orderDetail['discounts'])['type']) {
            return $totalOrderValue -  current($orderDetail['discounts'])['value'];
        }

        return $totalOrderValue *  current($orderDetail['discounts'])['value'] / 100;
    }
}
