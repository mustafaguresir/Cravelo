<?php
declare(strict_types=1);

namespace App\Model;

use App\Enum\OrderStatus;

final class Order
{
    /** @var OrderItem[] */
    public array $items = [];

    public function __construct(
        public int $id,
        public int $customerId,
        public int $restaurantId,
        public ?int $courierId = null,
        public OrderStatus $status = OrderStatus::ALINDI,
        public float $totalAmount = 0.0,
        public float $deliveryLatitude = 40.9850,
        public float $deliveryLongitude = 29.0280,
        public ?string $estimatedDeliveryTime = null,
        public ?string $createdAt = null,
        array $items = [],
        public ?int $deliveryX = 18,
        public ?int $deliveryY = 14
    ) {
        $this->items = $items;
        $this->createdAt = $createdAt ?? date('Y-m-d H:i:s');
        $this->deliveryX = $deliveryX ?? (int)round($deliveryLatitude);
        $this->deliveryY = $deliveryY ?? (int)round($deliveryLongitude);
    }

    public function addItem(OrderItem $item): void
    {
        $this->items[] = $item;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'customer_id' => $this->customerId,
            'restaurant_id' => $this->restaurantId,
            'courier_id' => $this->courierId,
            'status' => $this->status->value,
            'total_amount' => $this->totalAmount,
            'delivery_latitude' => $this->deliveryLatitude,
            'delivery_longitude' => $this->deliveryLongitude,
            'delivery_x' => $this->deliveryX,
            'delivery_y' => $this->deliveryY,
            'estimated_delivery_time' => $this->estimatedDeliveryTime,
            'created_at' => $this->createdAt,
            'items' => array_map(fn(OrderItem $i) => $i->toArray(), $this->items)
        ];
    }

    public static function fromArray(array $data): self
    {
        $items = [];
        if (!empty($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $itemData) {
                $items[] = $itemData instanceof OrderItem ? $itemData : OrderItem::fromArray($itemData);
            }
        }

        $lat = (float)($data['delivery_latitude'] ?? $data['deliveryX'] ?? 40.9850);
        $lon = (float)($data['delivery_longitude'] ?? $data['deliveryY'] ?? 29.0280);
        $delX = isset($data['delivery_x']) ? (int)$data['delivery_x'] : 18;
        $delY = isset($data['delivery_y']) ? (int)$data['delivery_y'] : 14;

        return new self(
            id: (int)$data['id'],
            customerId: (int)$data['customer_id'],
            restaurantId: (int)$data['restaurant_id'],
            courierId: isset($data['courier_id']) ? (int)$data['courier_id'] : null,
            status: OrderStatus::from((string)$data['status']),
            totalAmount: (float)$data['total_amount'],
            deliveryLatitude: $lat,
            deliveryLongitude: $lon,
            estimatedDeliveryTime: $data['estimated_delivery_time'] ?? null,
            createdAt: $data['created_at'] ?? null,
            items: $items,
            deliveryX: $delX,
            deliveryY: $delY
        );
    }
}
