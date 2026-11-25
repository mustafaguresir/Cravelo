<?php
declare(strict_types=1);

namespace App\Repository\InMemory;

use App\Model\Restaurant;
use App\Model\MenuItem;
use App\Repository\RestaurantRepositoryInterface;

final class InMemoryRestaurantRepository implements RestaurantRepositoryInterface
{
    /** @var array<int, Restaurant> */
    public array $restaurants = [];

    /** @var array<int, MenuItem[]> */
    public array $menuItems = [];

    private int $autoIncrementId = 1;

    public function findAll(): array
    {
        return array_values($this->restaurants);
    }

    public function findById(int $id): ?Restaurant
    {
        return $this->restaurants[$id] ?? null;
    }

    public function findByUserId(int $userId): ?Restaurant
    {
        foreach ($this->restaurants as $r) {
            if ($r->userId === $userId) {
                return $r;
            }
        }
        return null;
    }

    public function save(Restaurant $restaurant): int
    {
        if ($restaurant->id <= 0) {
            $restaurant->id = $this->autoIncrementId++;
        }
        $this->restaurants[$restaurant->id] = $restaurant;
        return $restaurant->id;
    }

    public function getMenuItems(int $restaurantId): array
    {
        return $this->menuItems[$restaurantId] ?? [];
    }

    public function findMenuItemById(int $id): ?MenuItem
    {
        foreach ($this->menuItems as $items) {
            foreach ($items as $item) {
                if ($item->id === $id) {
                    return $item;
                }
            }
        }
        return null;
    }

    public function saveMenuItem(MenuItem $item): int
    {
        $this->menuItems[$item->restaurantId][] = $item;
        return $item->id;
    }
}
