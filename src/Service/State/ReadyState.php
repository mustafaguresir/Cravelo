<?php
declare(strict_types=1);

namespace App\Service\State;

use App\Model\Order;
use App\Enum\OrderStatus;
use App\Exception\InvalidOrderTransitionException;

final class ReadyState implements OrderStateInterface
{
    public function proceed(Order $order, OrderStatus $nextStatus): void
    {
        // Hazır durumundan sadece 'Yolda' aşamasına geçilebilir
        if ($nextStatus !== OrderStatus::YOLDA) {
            throw new InvalidOrderTransitionException(
                "Hazır aşamasındaki sipariş yalnızca 'yolda' durumuna geçebilir. İstenen: {$nextStatus->value}"
            );
        }
    }

    public function canCancel(Order $order): bool
    {
        return false;
    }
}
