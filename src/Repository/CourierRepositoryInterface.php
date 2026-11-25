<?php
namespace App\Repository;

use App\Model\Courier;

interface CourierRepositoryInterface
{
    public function findAll(): array;
    public function findAvailable(): array;
    public function findById(int $id): ?Courier;
    public function findByUserId(int $userId): ?Courier;
    public function save(Courier $courier): int;
    public function incrementActiveOrders(int $courierId): void;
    public function decrementActiveOrders(int $courierId): void;
}
