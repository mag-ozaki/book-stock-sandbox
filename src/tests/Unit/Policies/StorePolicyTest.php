<?php

namespace Tests\Unit\Policies;

use App\Models\Admin;
use App\Models\Store;
use App\Models\StoreUser;
use App\Policies\StorePolicy;
use PHPUnit\Framework\TestCase;

class StorePolicyTest extends TestCase
{
    private StorePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new StorePolicy();
    }

    private function makeAdmin(): Admin
    {
        return new Admin();
    }

    private function makeStoreUser(string $role = 'owner'): StoreUser
    {
        $user = new StoreUser();
        $user->role = $role;
        return $user;
    }

    // before()

    public function test_before_returns_true_for_admin(): void
    {
        $this->assertTrue($this->policy->before($this->makeAdmin(), 'viewAny'));
    }

    public function test_before_returns_false_for_owner(): void
    {
        $this->assertFalse($this->policy->before($this->makeStoreUser('owner'), 'viewAny'));
    }

    public function test_before_returns_false_for_employee(): void
    {
        $this->assertFalse($this->policy->before($this->makeStoreUser('employee'), 'viewAny'));
    }

    // 以下は admin のみが到達できる（before でガードされるため StoreUser は不到達）

    public function test_admin_can_view_any_store(): void
    {
        $this->assertTrue($this->policy->viewAny($this->makeAdmin()));
    }

    public function test_admin_can_view_store(): void
    {
        $this->assertTrue($this->policy->view($this->makeAdmin(), new Store()));
    }

    public function test_admin_can_create_store(): void
    {
        $this->assertTrue($this->policy->create($this->makeAdmin()));
    }

    public function test_admin_can_update_store(): void
    {
        $this->assertTrue($this->policy->update($this->makeAdmin(), new Store()));
    }

    public function test_admin_can_delete_store(): void
    {
        $this->assertTrue($this->policy->delete($this->makeAdmin(), new Store()));
    }
}
