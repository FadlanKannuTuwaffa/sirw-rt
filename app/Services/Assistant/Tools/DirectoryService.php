<?php

namespace App\Services\Assistant\Tools;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DirectoryService
{
    public function searchDirectory(string $query, int $residentId): array
    {
        $keyword = trim($query);

        if (Str::length($keyword) < 3) {
            return [
                'summary' => [
                    'count' => 0,
                    'route' => route('resident.directory'),
                    'note' => 'Query terlalu pendek, minimal 3 karakter.',
                ],
                'items' => [],
            ];
        }

        $baseQuery = User::query()
            ->residents()
            ->where('id', '!=', $residentId)
            ->where(function ($builder) use ($keyword) {
                $builder
                    ->where('name', 'like', '%' . $keyword . '%')
                    ->orWhere('username', 'like', '%' . $keyword . '%')
                    ->orWhere('phone', 'like', '%' . $keyword . '%');
            });

        $count = (clone $baseQuery)->count();

        /** @var Collection<int, User> $users */
        $users = (clone $baseQuery)
            ->orderBy('name')
            ->limit(10)
            ->get([
                'id',
                'name',
                'username',
                'phone',
                'masked_phone',
                'status',
                'profile_photo_path',
            ]);

        $items = $users->map(static function (User $user): array {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'masked_phone' => $user->masked_phone,
                'status' => $user->status,
                'profile_photo_url' => $user->profile_photo_url,
            ];
        })->all();

        return [
            'summary' => [
                'count' => $count,
                'query' => $keyword,
                'route' => route('resident.directory'),
            ],
            'items' => $items,
        ];
    }
}

