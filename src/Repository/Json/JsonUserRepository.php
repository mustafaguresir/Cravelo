<?php
declare(strict_types=1);

namespace App\Repository\Json;

use App\Model\User;
use App\Repository\UserRepositoryInterface;
use App\Storage\JsonStorageHelper;

final class JsonUserRepository implements UserRepositoryInterface
{
    private string $filePath;

    public function __construct(?string $filePath = null)
    {
        $this->filePath = $filePath ?? dirname(__DIR__, 3) . '/storage/users.json';
    }

    public function findById(int $id): ?User
    {
        $records = JsonStorageHelper::read($this->filePath);
        foreach ($records as $row) {
            if ((int)$row['id'] === $id) {
                return User::fromArray($row);
            }
        }
        return null;
    }

    public function findByUsername(string $username): ?User
    {
        $records = JsonStorageHelper::read($this->filePath);
        $search = trim($username);
        foreach ($records as $row) {
            if (strcasecmp((string)$row['username'], $search) === 0 ||
                (isset($row['email']) && strcasecmp((string)$row['email'], $search) === 0)) {
                return User::fromArray($row);
            }
        }
        return null;
    }

    public function save(User $user): int
    {
        return (int)JsonStorageHelper::update($this->filePath, static function (array $records) use ($user): array {
            $maxId = 0;
            $foundIndex = -1;

            foreach ($records as $index => $row) {
                $id = (int)$row['id'];
                if ($id > $maxId) {
                    $maxId = $id;
                }
                if ($user->id > 0 && $id === $user->id) {
                    $foundIndex = $index;
                }
            }

            if ($user->id <= 0) {
                $user->id = $maxId + 1;
            }

            $data = $user->toArray();
            if ($foundIndex >= 0) {
                $records[$foundIndex] = $data;
            } else {
                $records[] = $data;
            }

            return ['data' => $records, 'result' => $user->id];
        });
    }

    public function findAll(): array
    {
        $records = JsonStorageHelper::read($this->filePath);
        return array_map(fn(array $row) => User::fromArray($row), $records);
    }

    public function delete(int $id): bool
    {
        return (bool)JsonStorageHelper::update($this->filePath, static function (array $records) use ($id): array {
            $newRecords = array_values(array_filter($records, static fn(array $r): bool => (int)$r['id'] !== $id));
            $changed = count($records) !== count($newRecords);
            return ['data' => $newRecords, 'result' => $changed, 'write' => $changed];
        });
    }
}

