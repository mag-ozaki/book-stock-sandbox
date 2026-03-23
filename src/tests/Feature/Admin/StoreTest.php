<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Store;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use DatabaseTransactions;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Admin::factory()->create();
    }

    public function test_admin_can_view_stores_index(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.stores.index'))
            ->assertOk();
    }

    public function test_admin_can_view_create_form(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.stores.create'))
            ->assertOk();
    }

    public function test_admin_can_create_store(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.stores.store'), [
                'name'    => 'New Store',
                'address' => 'Tokyo',
                'phone'   => '03-1234-5678',
            ])
            ->assertRedirect(route('admin.stores.index'));

        $this->assertDatabaseHas('stores', ['name' => 'New Store']);
    }

    public function test_admin_can_view_edit_form(): void
    {
        $store = Store::factory()->create();

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.stores.edit', $store))
            ->assertOk();
    }

    public function test_admin_can_update_store(): void
    {
        $store = Store::factory()->create();

        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.stores.update', $store), [
                'name' => 'Updated Store',
            ])
            ->assertRedirect(route('admin.stores.index'));

        $this->assertDatabaseHas('stores', ['id' => $store->id, 'name' => 'Updated Store']);
    }

    public function test_admin_can_delete_store(): void
    {
        $store = Store::factory()->create();

        $this->actingAs($this->admin, 'admin')
            ->delete(route('admin.stores.destroy', $store))
            ->assertRedirect(route('admin.stores.index'));

        $this->assertDatabaseMissing('stores', ['id' => $store->id]);
    }

    public function test_unauthenticated_request_redirects_to_admin_login(): void
    {
        $this->get(route('admin.stores.index'))
            ->assertRedirect(route('admin.login'));
    }
}
