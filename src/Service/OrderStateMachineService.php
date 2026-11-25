<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Order;
use App\Enum\OrderStatus;
use App\Exception\InvalidOrderTransitionException;
use App\Service\State\OrderStateInterface;
use App\Service\State\ReceivedState;
use App\Service\State\PreparingState;
use App\Service\State\ReadyState;
use App\Service\State\OnWayState;
use App\Service\State\DeliveredState;
use App\Service\State\CancelledState;
use App\Repository\OrderRepositoryInterface;

final class OrderStateMachineService
{
    public function __construct(
        private ?OrderRepositoryInterface $orderRepo = null
    ) {}

    public function getStateInstance(OrderStatus $status): OrderStateInterface
    {
        return match ($status) {
            OrderStatus::ALINDI => new ReceivedState(),
            OrderStatus::HAZIRLANIYOR => new PreparingState(),
            OrderStatus::HAZIR => new ReadyState(),
            OrderStatus::YOLDA => new OnWayState(),
            OrderStatus::TESLIM_EDILDI => new DeliveredState(),
            OrderStatus::IPTAL => new CancelledState(),
        };
    }

    public function canTransition(Order $order, OrderStatus $targetStatus): bool
    {
        try {
            $state = $this->getStateInstance($order->status);
            $state->proceed($order, $targetStatus);
            return true;
        } catch (InvalidOrderTransitionException) {
            return false;
        }
    }

    public function transition(Order $order, OrderStatus $targetStatus): Order
    {
        $state = $this->getStateInstance($order->status);
        $state->proceed($order, $targetStatus);

        if ($this->orderRepo) {
            $updated = $this->orderRepo->updateStatusConditional($order->id, $order->status, $targetStatus);
            if (!$updated) {
                throw new InvalidOrderTransitionException(
                    "Koşullu güncelleme başarısız oldu! Sipariş başka bir işlem tarafından güncellenmiş olabilir."
                );
            }
        }

        $order->status = $targetStatus;
        return $order;
    }

    public function cancel(Order $order): bool
    {
        $state = $this->getStateInstance($order->status);
        if (!$state->canCancel($order)) {
            throw new InvalidOrderTransitionException("Sipariş hazırlanmaya başlandıktan sonra iptal edilemez!");
        }

        $this->transition($order, OrderStatus::IPTAL);
        return true;
    }
}
