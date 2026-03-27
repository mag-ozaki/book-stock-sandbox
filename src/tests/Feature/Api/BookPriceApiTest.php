<?php

namespace Tests\Feature\Api;

use App\Models\Admin;
use App\Models\Book;
use App\Models\Store;
use App\Models\StoreApiKey;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class BookPriceApiTest extends TestCase
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

    private function apiUrl(int|string $storeId = null, string $janCode = null): string
    {
        $storeId = $storeId ?? $this->store->id;
        $janCode = $janCode ?? $this->book->jan_code;

        return "/api/stores/{$storeId}/books/{$janCode}";
    }

    /**
     * 有効なキーで既存 JANコードを照会すると 200 が返り、正しい書籍情報が含まれる。
     */
    public function test_valid_key_returns_book_data(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->plain)
            ->getJson($this->apiUrl());

        $response->assertOk()
            ->assertJsonPath('data.id', $this->book->id)
            ->assertJsonPath('data.jan_code', $this->book->jan_code)
            ->assertJsonPath('data.price', $this->book->price);
    }

    /**
     * price が null の書籍を照会すると 200 が返り、data.price が null になる。
     */
    public function test_book_with_null_price_returns_null_price(): void
    {
        $bookWithNullPrice = Book::factory()->create(['price' => null]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->plain)
            ->getJson($this->apiUrl(janCode: $bookWithNullPrice->jan_code));

        $response->assertOk()
            ->assertJsonPath('data.price', null);
    }

    /**
     * 存在しない JANコードを照会すると 404 が返り、message キーが存在する。
     */
    public function test_nonexistent_jan_code_returns_404(): void
    {
        $nonExistentJanCode = str_repeat('0', 26);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->plain)
            ->getJson($this->apiUrl(janCode: $nonExistentJanCode));

        $response->assertNotFound()
            ->assertJsonStructure(['message']);
    }

    /**
     * 25桁の JANコードはルート制約で弾かれ 404 が返る。
     */
    public function test_25_digit_jan_code_returns_404_by_route_constraint(): void
    {
        $jan25 = str_repeat('1', 25);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->plain)
            ->getJson($this->apiUrl(janCode: $jan25));

        $response->assertNotFound();
    }

    /**
     * 数字以外を含む JANコードはルート制約で弾かれ 404 が返る。
     */
    public function test_jan_code_with_non_digits_returns_404_by_route_constraint(): void
    {
        $invalidJan = 'ABC' . str_repeat('1', 23);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->plain)
            ->getJson($this->apiUrl(janCode: $invalidJan));

        $response->assertNotFound();
    }

    /**
     * Bearer トークンなしのリクエストは 401 が返る。
     */
    public function test_missing_bearer_token_returns_401(): void
    {
        $response = $this->getJson($this->apiUrl());

        $response->assertUnauthorized();
    }

    /**
     * 無効化済みキー（is_active=false）では 401 が返る。
     */
    public function test_inactive_key_returns_401(): void
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
            ->getJson($this->apiUrl());

        $response->assertUnauthorized();
    }

    /**
     * 有効期限切れキー（expires_at が過去）では 401 が返る。
     */
    public function test_expired_key_returns_401(): void
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
            ->getJson($this->apiUrl());

        $response->assertUnauthorized();
    }

    /**
     * 別店舗のキーで照会すると 403 が返る。
     */
    public function test_key_from_different_store_returns_403(): void
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
            ->getJson($this->apiUrl());

        $response->assertForbidden();
    }

    /**
     * IP制限あり・許可外 IP からの照会は 403 が返る。
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
            ->getJson($this->apiUrl());

        $response->assertForbidden();
    }

    /**
     * IP制限あり・許可 IP からの照会は 200 が返る。
     */
    public function test_request_from_allowed_ip_returns_200(): void
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
            ->getJson($this->apiUrl());

        $response->assertOk()
            ->assertJsonPath('data.jan_code', $this->book->jan_code);
    }

    /**
     * 存在しない store_id では Store Model Binding により 404 が返る。
     */
    public function test_nonexistent_store_id_returns_404(): void
    {
        $nonExistentStoreId = 99999999;

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->plain)
            ->getJson($this->apiUrl(storeId: $nonExistentStoreId));

        $response->assertNotFound();
    }
}
