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
        if ($order->deliveryMode === 'takeaway') {
            if ($nextStatus !== OrderStatus::TESLIM_EDILDI) {
                throw new InvalidOrderTransitionException(
                    "Gel-Al (takeaway) siparişi hazır aşamasından doğrudan 'teslim_edildi' durumuna geçebilir. İstenen: {$nextStatus->value}"
                );
            }
            return;
        }

        // Kuryeli siparişlerde Hazır durumundan sadece 'Yolda' aşamasına geçilebilir ve kurye atanmış olmalıdır
        if ($nextStatus !== OrderStatus::YOLDA) {
            throw new InvalidOrderTransitionException(
                "Hazır aşamasındaki sipariş yalnızca 'yolda' durumuna geçebilir. İstenen: {$nextStatus->value}"
            );
        }

        if ($order->courierId === null || $order->courierId <= 0) {
            throw new InvalidOrderTransitionException(
                "Kurye atanmamış bir sipariş 'yolda' durumuna geçirilemez! Lütfen önce müsait bir kurye atanmasını bekleyin."
            );
        }
    }

    public function canCancel(Order $order): bool
    {
        return false;
    }
}
