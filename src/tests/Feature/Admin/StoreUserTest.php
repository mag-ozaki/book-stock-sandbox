<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Store;
use App\Models\StoreUser;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class StoreUserTest extends TestCase
{
    use DatabaseTransactions;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Admin::factory()->create();
    }

    public function test_admin_can_view_store_users_index(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.store-users.index'))
            ->assertOk();
    }

    public function test_admin_can_view_create_form(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.store-users.create'))
            ->assertOk();
    }

    public function test_admin_can_create_store_user(): void
    {
        $store = Store::factory()->create();

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.store-users.store'), [
                'store_id'              => $store->id,
                'name'                  => 'New User',
                'email'                 => 'newuser@example.com',
                'password'              => 'password',
                'password_confirmation' => 'password',
                'role'                  => 'employee',
            ])
            ->assertRedirect(route('admin.store-users.index'));

        $this->assertDatabaseHas('store_users', ['email' => 'newuser@example.com']);
    }

    public function test_admin_can_view_edit_form(): void
    {
        $storeUser = StoreUser::factory()->create();

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.store-users.edit', $storeUser))
            ->assertOk();
    }

    public function test_admin_can_update_store_user(): void
    {
        $storeUser = StoreUser::factory()->create();

        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.store-users.update', $storeUser), [
                'store_id' => $storeUser->store_id,
                'name'     => 'Updated Name',
                'email'    => $storeUser->email,
                'role'     => 'owner',
            ])
            ->assertRedirect(route('admin.store-users.index'));

        $this->assertDatabaseHas('store_users', ['id' => $storeUser->id, 'name' => 'Updated Name', 'role' => 'owner']);
    }

    public function test_admin_can_delete_store_user(): void
    {
        $storeUser = StoreUser::factory()->create();

        $this->actingAs($this->admin, 'admin')
            ->delete(route('admin.store-users.destroy', $storeUser))
            ->assertRedirect(route('admin.store-users.index'));

        $this->assertDatabaseMissing('store_users', ['id' => $storeUser->id]);
    }

    public function test_unauthenticated_request_redirects_to_admin_login(): void
    {
        $this->get(route('admin.store-users.index'))
            ->assertRedirect(route('admin.login'));
    }
}
