<?php
declare(strict_types=1);

namespace App\Service\State;

use App\Model\Order;
use App\Enum\OrderStatus;
use App\Exception\InvalidOrderTransitionException;

final class CancelledState implements OrderStateInterface
{
    public function proceed(Order $order, OrderStatus $nextStatus): void
    {
        throw new InvalidOrderTransitionException("İptal edilmiş sipariş yeniden açılamaz!");
    }

    public function canCancel(Order $order): bool
    {
        return false;
    }
}
