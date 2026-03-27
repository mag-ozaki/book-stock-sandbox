<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Store;
use App\Models\StoreApiKey;
use App\Models\StoreUser;
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

    // --- 正常系 ---

    public function test_admin_can_view_api_keys_index(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.stores.api-keys.index', $this->store))
            ->assertOk();
    }

    public function test_admin_can_issue_api_key(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.stores.api-keys.store', $this->store), [
                'name' => 'レジ1',
            ]);

        $response->assertRedirect(route('admin.stores.api-keys.index', $this->store));

        // DB に key_hash が保存されている
        $this->assertDatabaseHas('store_api_keys', [
            'store_id' => $this->store->id,
            'name'     => 'レジ1',
        ]);

        // セッションに newly_issued_key がフラッシュされている
        $response->assertSessionHas('newly_issued_key');
    }

    public function test_newly_issued_key_is_passed_to_inertia_props_on_first_visit(): void
    {
        // 発行後にリダイレクト先を開くと props に newlyIssuedKey が渡される
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.stores.api-keys.store', $this->store), [
                'name' => 'レジ2',
            ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.stores.api-keys.index', $this->store))
            ->assertInertia(fn ($page) => $page->where('newlyIssuedKey', fn ($value) => $value !== null));
    }

    public function test_newly_issued_key_is_null_on_second_visit(): void
    {
        // 2 回目のアクセスではフラッシュが消費済みなので null
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.stores.api-keys.index', $this->store))
            ->assertInertia(fn ($page) => $page->where('newlyIssuedKey', null));
    }

    public function test_admin_can_deactivate_api_key(): void
    {
        $apiKey = StoreApiKey::factory()->create([
            'store_id'   => $this->store->id,
            'created_by' => $this->admin->id,
            'is_active'  => true,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->patch(route('admin.stores.api-keys.update', [$this->store, $apiKey]), [
                'is_active' => false,
            ])
            ->assertRedirect(route('admin.stores.api-keys.index', $this->store));

        $this->assertDatabaseHas('store_api_keys', [
            'id'        => $apiKey->id,
            'is_active' => false,
        ]);
    }

    public function test_admin_can_reactivate_api_key(): void
    {
        $apiKey = StoreApiKey::factory()->create([
            'store_id'   => $this->store->id,
            'created_by' => $this->admin->id,
            'is_active'  => false,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->patch(route('admin.stores.api-keys.update', [$this->store, $apiKey]), [
                'is_active' => true,
            ])
            ->assertRedirect(route('admin.stores.api-keys.index', $this->store));

        $this->assertDatabaseHas('store_api_keys', [
            'id'        => $apiKey->id,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_delete_api_key(): void
    {
        $apiKey = StoreApiKey::factory()->create([
            'store_id'   => $this->store->id,
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->delete(route('admin.stores.api-keys.destroy', [$this->store, $apiKey]))
            ->assertRedirect(route('admin.stores.api-keys.index', $this->store));

        $this->assertDatabaseMissing('store_api_keys', ['id' => $apiKey->id]);
    }

    // --- 認証・認可 ---

    public function test_unauthenticated_user_is_redirected_to_admin_login(): void
    {
        $this->get(route('admin.stores.api-keys.index', $this->store))
            ->assertRedirect(route('admin.login'));
    }

    public function test_owner_cannot_access_index(): void
    {
        // web ガードのユーザーは admin ガード専用ルートに入れず、admin.login へリダイレクト
        $owner = StoreUser::factory()->owner()->create(['store_id' => $this->store->id]);

        $this->actingAs($owner, 'web')
            ->get(route('admin.stores.api-keys.index', $this->store))
            ->assertRedirect(route('admin.login'));
    }

    public function test_employee_cannot_access_index(): void
    {
        // web ガードのユーザーは admin ガード専用ルートに入れず、admin.login へリダイレクト
        $employee = StoreUser::factory()->employee()->create(['store_id' => $this->store->id]);

        $this->actingAs($employee, 'web')
            ->get(route('admin.stores.api-keys.index', $this->store))
            ->assertRedirect(route('admin.login'));
    }

    public function test_update_with_different_store_api_key_returns_403(): void
    {
        $otherStore = Store::factory()->create();
        $apiKey     = StoreApiKey::factory()->create([
            'store_id'   => $otherStore->id,
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->patch(route('admin.stores.api-keys.update', [$this->store, $apiKey]), [
                'is_active' => false,
            ])
            ->assertForbidden();
    }

    public function test_destroy_with_different_store_api_key_returns_403(): void
    {
        $otherStore = Store::factory()->create();
        $apiKey     = StoreApiKey::factory()->create([
            'store_id'   => $otherStore->id,
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->delete(route('admin.stores.api-keys.destroy', [$this->store, $apiKey]))
            ->assertForbidden();
    }

    // --- バリデーション ---

    public function test_issue_fails_when_name_is_empty(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.stores.api-keys.store', $this->store), [
                'name' => '',
            ])
            ->assertSessionHasErrors('name');
    }

    public function test_issue_fails_with_invalid_ip(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.stores.api-keys.store', $this->store), [
                'name'        => 'レジ1',
                'allowed_ips' => ['not-an-ip'],
            ])
            ->assertSessionHasErrors('allowed_ips.0');
    }

    public function test_issue_fails_with_past_expires_at(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.stores.api-keys.store', $this->store), [
                'name'       => 'レジ1',
                'expires_at' => now()->subDay()->toDateTimeString(),
            ])
            ->assertSessionHasErrors('expires_at');
    }
}
