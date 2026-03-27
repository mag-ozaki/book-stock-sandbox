<?php

namespace Tests\Unit\Models;

use App\Models\Admin;
use App\Models\Store;
use App\Models\StoreApiKey;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class StoreApiKeyTest extends TestCase
{
    use DatabaseTransactions;

    private Admin $admin;
    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Admin::factory()->create();
        $this->store = Store::factory()->create();
    }

    public function test_store_api_key_belongs_to_store(): void
    {
        $apiKey = StoreApiKey::factory()->create([
            'store_id'   => $this->store->id,
            'created_by' => $this->admin->id,
        ]);

        $this->assertEquals($this->store->id, $apiKey->store->id);
    }

    public function test_store_api_key_belongs_to_admin_as_created_by(): void
    {
        $apiKey = StoreApiKey::factory()->create([
            'store_id'   => $this->store->id,
            'created_by' => $this->admin->id,
        ]);

        $this->assertEquals($this->admin->id, $apiKey->createdBy->id);
    }

    public function test_allowed_ips_is_cast_to_array(): void
    {
        $apiKey = StoreApiKey::factory()->create([
            'store_id'    => $this->store->id,
            'created_by'  => $this->admin->id,
            'allowed_ips' => ['192.168.1.1', '10.0.0.1'],
        ]);

        $fresh = $apiKey->fresh();
        $this->assertIsArray($fresh->allowed_ips);
        $this->assertContains('192.168.1.1', $fresh->allowed_ips);
    }

    public function test_is_active_is_cast_to_boolean(): void
    {
        $apiKey = StoreApiKey::factory()->create([
            'store_id'   => $this->store->id,
            'created_by' => $this->admin->id,
            'is_active'  => true,
        ]);

        $this->assertIsBool($apiKey->fresh()->is_active);
    }

    public function test_expires_at_is_cast_to_datetime(): void
    {
        $apiKey = StoreApiKey::factory()->create([
            'store_id'   => $this->store->id,
            'created_by' => $this->admin->id,
            'expires_at' => '2030-12-31 00:00:00',
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $apiKey->fresh()->expires_at);
    }
}
