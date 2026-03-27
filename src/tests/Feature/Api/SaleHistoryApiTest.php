<?php

namespace Tests\Feature\Api;

use App\Models\Admin;
use App\Models\Book;
use App\Models\Store;
use App\Models\StoreApiKey;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class SaleHistoryApiTest extends TestCase
{
    use DatabaseTransactions;

    private Store $store;
    private Book $book;
    private Admin $admin;
    private string $plain;
    private StoreApiKey $apiKey;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store  = Store::factory()->create();
        $this->book   = Book::factory()->create();
        $this->admin  = Admin::factory()->create();
        $this->plain  = Str::random(40);
        $this->apiKey = StoreApiKey::factory()->create([
            'store_id'    => $this->store->id,
            'key_hash'    => hash('sha256', $this->plain),
            'is_active'   => true,
            'expires_at'  => null,
            'allowed_ips' => null,
            'created_by'  => $this->admin->id,
        ]);
    }

    private function apiUrl(int|string|null $storeId = null): string
    {
        $storeId = $storeId ?? $this->store->id;

        return "/api/stores/{$storeId}/sale-histories";
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'jan_code'  => $this->book->jan_code,
            'quantity'  => 2,
            'sold_at'   => '2026-03-27T10:30:00+09:00',
        ], $overrides);
    }

    /**
     * 有効なAPIキーで正しい店舗に POST すると 201 が返り、必要なフィールドが含まれる。
     */
    public function test_valid_api_key_returns_201_with_expected_fields(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->plain)
            ->postJson($this->apiUrl(), $this->validPayload());

        $response->assertCreated()
            ->assertJsonPath('data.store_id', $this->store->id)
            ->assertJsonPath('data.book.id', $this->book->id)
            ->assertJsonPath('data.quantity', 2)
            ->assertJsonStructure(['data' => ['id', 'store_id', 'book', 'quantity']]);
    }

    /**
     * sold_at を省略して POST しても 201 が返る（sold_at は任意）。
     */
    public function test_post_without_sold_at_returns_201(): void
    {
        $payload = [
            'jan_code'  => $this->book->jan_code,
            'quantity'  => 1,
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->plain)
            ->postJson($this->apiUrl(), $payload);

        $response->assertCreated();
    }

    /**
     * APIキーなしで POST すると 401 が返る。
     */
    public function test_missing_api_key_returns_401(): void
    {
        $response = $this->postJson($this->apiUrl(), $this->validPayload());

        $response->assertUnauthorized();
    }

    /**
     * 無効化済みAPIキー（is_active=false）で POST すると 401 が返る。
     */
    public function test_inactive_api_key_returns_401(): void
    {
        $inactivePlain = Str::random(40);
        StoreApiKey::factory()->create([
            'store_id'   => $this->store->id,
            'key_hash'   => hash('sha256', $inactivePlain),
            'is_active'  => false,
            'expires_at' => null,
            'created_by' => $this->admin->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $inactivePlain)
            ->postJson($this->apiUrl(), $this->validPayload());

        $response->assertUnauthorized();
    }

    /**
     * 有効期限切れのAPIキーで POST すると 401 が返る。
     */
    public function test_expired_api_key_returns_401(): void
    {
        $expiredPlain = Str::random(40);
        StoreApiKey::factory()->create([
            'store_id'   => $this->store->id,
            'key_hash'   => hash('sha256', $expiredPlain),
            'is_active'  => true,
            'expires_at' => now()->subDay(),
            'created_by' => $this->admin->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $expiredPlain)
            ->postJson($this->apiUrl(), $this->validPayload());

        $response->assertUnauthorized();
    }

    /**
     * 別店舗のAPIキーで POST すると 403 が返る。
     */
    public function test_api_key_from_different_store_returns_403(): void
    {
        $otherStore = Store::factory()->create();
        $otherPlain = Str::random(40);
        StoreApiKey::factory()->create([
            'store_id'   => $otherStore->id,
            'key_hash'   => hash('sha256', $otherPlain),
            'is_active'  => true,
            'expires_at' => null,
            'created_by' => $this->admin->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $otherPlain)
            ->postJson($this->apiUrl(), $this->validPayload());

        $response->assertForbidden();
    }

    /**
     * IP制限あり・許可外IPから POST すると 403 が返る。
     */
    public function test_request_from_disallowed_ip_returns_403(): void
    {
        $ipRestrictedPlain = Str::random(40);
        StoreApiKey::factory()->create([
            'store_id'    => $this->store->id,
            'key_hash'    => hash('sha256', $ipRestrictedPlain),
            'is_active'   => true,
            'expires_at'  => null,
            'allowed_ips' => ['192.168.1.1', '10.0.0.1'],
            'created_by'  => $this->admin->id,
        ]);

        $response = $this->withServerVariables(['REMOTE_ADDR' => '9.9.9.9'])
            ->withHeader('Authorization', 'Bearer ' . $ipRestrictedPlain)
            ->postJson($this->apiUrl(), $this->validPayload());

        $response->assertForbidden();
    }

    /**
     * IP制限あり・許可IPから POST すると 201 が返る。
     */
    public function test_request_from_allowed_ip_returns_201(): void
    {
        $ipRestrictedPlain = Str::random(40);
        StoreApiKey::factory()->create([
            'store_id'    => $this->store->id,
            'key_hash'    => hash('sha256', $ipRestrictedPlain),
            'is_active'   => true,
            'expires_at'  => null,
            'allowed_ips' => ['192.168.1.1', '10.0.0.1'],
            'created_by'  => $this->admin->id,
        ]);

        $response = $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.1'])
            ->withHeader('Authorization', 'Bearer ' . $ipRestrictedPlain)
            ->postJson($this->apiUrl(), $this->validPayload());

        $response->assertCreated();
    }

    /**
     * 存在しない jan_code（26桁の未登録コード）で POST すると 422 が返る。
     */
    public function test_nonexistent_jan_code_returns_422(): void
    {
        $unregisteredJanCode = str_repeat('9', 26);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->plain)
            ->postJson($this->apiUrl(), $this->validPayload(['jan_code' => $unregisteredJanCode]));

        $response->assertUnprocessable()
            ->assertJsonStructure(['errors' => ['jan_code']]);
    }

    /**
     * quantity=0 で POST すると 422 が返る。
     */
    public function test_quantity_zero_returns_422(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->plain)
            ->postJson($this->apiUrl(), $this->validPayload(['quantity' => 0]));

        $response->assertUnprocessable()
            ->assertJsonStructure(['errors' => ['quantity']]);
    }
}
