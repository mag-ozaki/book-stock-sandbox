<?php

namespace Tests\Feature\Web;

use App\Models\StoreUser;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class StoreUserTest extends TestCase
{
    use DatabaseTransactions;

    public function test_owner_can_view_store_users(): void
    {
        $owner = StoreUser::factory()->owner()->create();

        $this->actingAs($owner, 'web')
            ->get(route('store-users.index'))
            ->assertOk();
    }

    public function test_employee_can_view_store_users(): void
    {
        $employee = StoreUser::factory()->employee()->create();

        $this->actingAs($employee, 'web')
            ->get(route('store-users.index'))
            ->assertOk();
    }

    public function test_owner_can_create_store_user(): void
    {
        $owner = StoreUser::factory()->owner()->create();

        $this->actingAs($owner, 'web')
            ->post(route('store-users.store'), [
                'name'                  => 'New Employee',
                'email'                 => 'employee@example.com',
                'password'              => 'password',
                'password_confirmation' => 'password',
                'role'                  => 'employee',
            ])
            ->assertRedirect(route('store-users.index'));

        $this->assertDatabaseHas('store_users', [
            'email'    => 'employee@example.com',
            'store_id' => $owner->store_id,
        ]);
    }

    public function test_employee_cannot_create_store_user(): void
    {
        $employee = StoreUser::factory()->employee()->create();

        $this->actingAs($employee, 'web')
            ->post(route('store-users.store'), [
                'name'                  => 'New User',
                'email'                 => 'newuser@example.com',
                'password'              => 'password',
                'password_confirmation' => 'password',
                'role'                  => 'employee',
            ])
            ->assertForbidden();
    }

    public function test_owner_can_update_store_user_in_own_store(): void
    {
        $owner    = StoreUser::factory()->owner()->create();
        $employee = StoreUser::factory()->employee()->create(['store_id' => $owner->store_id]);

        $this->actingAs($owner, 'web')
            ->put(route('store-users.update', $employee), [
                'name'  => 'Updated Name',
                'email' => $employee->email,
                'role'  => 'employee',
            ])
            ->assertRedirect(route('store-users.index'));

        $this->assertDatabaseHas('store_users', ['id' => $employee->id, 'name' => 'Updated Name']);
    }

    public function test_owner_cannot_update_store_user_from_other_store(): void
    {
        $owner           = StoreUser::factory()->owner()->create();
        $otherStoreUser  = StoreUser::factory()->employee()->create(); // 別店舗

        $this->actingAs($owner, 'web')
            ->put(route('store-users.update', $otherStoreUser), [
                'name'  => 'Updated Name',
                'email' => $otherStoreUser->email,
                'role'  => 'employee',
            ])
            ->assertForbidden();
    }

    public function test_employee_cannot_update_store_user(): void
    {
        $employee = StoreUser::factory()->employee()->create();
        $target   = StoreUser::factory()->owner()->create(['store_id' => $employee->store_id]);

        $this->actingAs($employee, 'web')
            ->put(route('store-users.update', $target), [
                'name'  => 'Updated Name',
                'email' => $target->email,
                'role'  => 'owner',
            ])
            ->assertForbidden();
    }

    public function test_owner_can_delete_store_user_in_own_store(): void
    {
        $owner    = StoreUser::factory()->owner()->create();
        $employee = StoreUser::factory()->employee()->create(['store_id' => $owner->store_id]);

        $this->actingAs($owner, 'web')
            ->delete(route('store-users.destroy', $employee))
            ->assertRedirect(route('store-users.index'));

        $this->assertDatabaseMissing('store_users', ['id' => $employee->id]);
    }

    public function test_owner_cannot_delete_themselves(): void
    {
        $owner = StoreUser::factory()->owner()->create();

        $this->actingAs($owner, 'web')
            ->delete(route('store-users.destroy', $owner))
            ->assertForbidden();
    }

    public function test_owner_cannot_delete_store_user_from_other_store(): void
    {
        $owner          = StoreUser::factory()->owner()->create();
        $otherStoreUser = StoreUser::factory()->employee()->create(); // 別店舗

        $this->actingAs($owner, 'web')
            ->delete(route('store-users.destroy', $otherStoreUser))
            ->assertForbidden();
    }

    public function test_employee_cannot_delete_store_user(): void
    {
        $employee = StoreUser::factory()->employee()->create();
        $target   = StoreUser::factory()->owner()->create(['store_id' => $employee->store_id]);

        $this->actingAs($employee, 'web')
            ->delete(route('store-users.destroy', $target))
            ->assertForbidden();
    }

    public function test_owner_can_view_create_form(): void
    {
        $owner = StoreUser::factory()->owner()->create();

        $this->actingAs($owner, 'web')
            ->get(route('store-users.create'))
            ->assertOk();
    }

    public function test_employee_cannot_view_create_form(): void
    {
        $employee = StoreUser::factory()->employee()->create();

        $this->actingAs($employee, 'web')
            ->get(route('store-users.create'))
            ->assertForbidden();
    }

    public function test_owner_can_view_edit_form_for_own_store_user(): void
    {
        $owner    = StoreUser::factory()->owner()->create();
        $employee = StoreUser::factory()->employee()->create(['store_id' => $owner->store_id]);

        $this->actingAs($owner, 'web')
            ->get(route('store-users.edit', $employee))
            ->assertOk();
    }

    public function test_employee_cannot_view_edit_form(): void
    {
        $employee = StoreUser::factory()->employee()->create();
        $target   = StoreUser::factory()->owner()->create(['store_id' => $employee->store_id]);

        $this->actingAs($employee, 'web')
            ->get(route('store-users.edit', $target))
            ->assertForbidden();
    }

    public function test_guest_cannot_access_store_users(): void
    {
        $this->get(route('store-users.index'))
            ->assertRedirect(route('login'));
    }
}
