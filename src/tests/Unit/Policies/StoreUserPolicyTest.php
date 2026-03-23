<?php

namespace Tests\Unit\Policies;

use App\Models\Admin;
use App\Models\StoreUser;
use App\Policies\StoreUserPolicy;
use PHPUnit\Framework\TestCase;

class StoreUserPolicyTest extends TestCase
{
    private StoreUserPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new StoreUserPolicy();
    }

    private function makeAdmin(): Admin
    {
        return new Admin();
    }

    private function makeUser(int $id, int $storeId, string $role): StoreUser
    {
        $user = new StoreUser();
        $user->id = $id;
        $user->store_id = $storeId;
        $user->role = $role;
        return $user;
    }

    // before()

    public function test_before_returns_true_for_admin(): void
    {
        $this->assertTrue($this->policy->before($this->makeAdmin(), 'viewAny'));
    }

    public function test_before_returns_null_for_store_user(): void
    {
        $this->assertNull($this->policy->before($this->makeUser(1, 1, 'owner'), 'viewAny'));
    }

    // viewAny

    public function test_owner_can_view_any(): void
    {
        $this->assertTrue($this->policy->viewAny($this->makeUser(1, 1, 'owner')));
    }

    public function test_employee_can_view_any(): void
    {
        $this->assertTrue($this->policy->viewAny($this->makeUser(1, 1, 'employee')));
    }

    // view

    public function test_user_can_view_same_store_user(): void
    {
        $actor  = $this->makeUser(1, 1, 'owner');
        $target = $this->makeUser(2, 1, 'employee');
        $this->assertTrue($this->policy->view($actor, $target));
    }

    public function test_user_cannot_view_other_store_user(): void
    {
        $actor  = $this->makeUser(1, 1, 'owner');
        $target = $this->makeUser(2, 2, 'employee');
        $this->assertFalse($this->policy->view($actor, $target));
    }

    // create

    public function test_owner_can_create_store_user(): void
    {
        $this->assertTrue($this->policy->create($this->makeUser(1, 1, 'owner')));
    }

    public function test_employee_cannot_create_store_user(): void
    {
        $this->assertFalse($this->policy->create($this->makeUser(1, 1, 'employee')));
    }

    // update

    public function test_owner_can_update_same_store_user(): void
    {
        $actor  = $this->makeUser(1, 1, 'owner');
        $target = $this->makeUser(2, 1, 'employee');
        $this->assertTrue($this->policy->update($actor, $target));
    }

    public function test_owner_cannot_update_other_store_user(): void
    {
        $actor  = $this->makeUser(1, 1, 'owner');
        $target = $this->makeUser(2, 2, 'employee');
        $this->assertFalse($this->policy->update($actor, $target));
    }

    public function test_employee_cannot_update_store_user(): void
    {
        $actor  = $this->makeUser(1, 1, 'employee');
        $target = $this->makeUser(2, 1, 'owner');
        $this->assertFalse($this->policy->update($actor, $target));
    }

    // delete

    public function test_owner_can_delete_same_store_user(): void
    {
        $actor  = $this->makeUser(1, 1, 'owner');
        $target = $this->makeUser(2, 1, 'employee');
        $this->assertTrue($this->policy->delete($actor, $target));
    }

    public function test_owner_cannot_delete_themselves(): void
    {
        $actor = $this->makeUser(1, 1, 'owner');
        $this->assertFalse($this->policy->delete($actor, $actor));
    }

    public function test_owner_cannot_delete_other_store_user(): void
    {
        $actor  = $this->makeUser(1, 1, 'owner');
        $target = $this->makeUser(2, 2, 'employee');
        $this->assertFalse($this->policy->delete($actor, $target));
    }

    public function test_employee_cannot_delete_store_user(): void
    {
        $actor  = $this->makeUser(1, 1, 'employee');
        $target = $this->makeUser(2, 1, 'owner');
        $this->assertFalse($this->policy->delete($actor, $target));
    }
}
