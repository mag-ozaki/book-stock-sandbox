<?php

namespace Database\Factories;

use App\Models\Admin;
use App\Models\Store;
use App\Models\StoreApiKey;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StoreApiKey>
 */
class StoreApiKeyFactory extends Factory
{
    protected $model = StoreApiKey::class;

    public function definition(): array
    {
        return [
            'store_id'     => Store::factory(),
            'name'         => fake()->words(2, true),
            'key_hash'     => hash('sha256', fake()->uuid()),
            'allowed_ips'  => null,
            'is_active'    => true,
            'last_used_at' => null,
            'expires_at'   => null,
            'created_by'   => Admin::factory(),
        ];
    }
}
