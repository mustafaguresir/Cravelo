<?php
declare(strict_types=1);

namespace App\Service\State;

use App\Model\Order;
use App\Enum\OrderStatus;
use App\Exception\InvalidOrderTransitionException;

final class DeliveredState implements OrderStateInterface
{
    public function proceed(Order $order, OrderStatus $nextStatus): void
    {
        throw new InvalidOrderTransitionException(
            "Teslim edilmiş sipariş nihai durumdadır, durumu değiştirilemez!"
        );
    }

    public function canCancel(Order $order): bool
    {
        return false;
    }
}
