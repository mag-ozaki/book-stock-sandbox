<?php

namespace Tests\Unit\Policies;

use App\Models\Admin;
use App\Models\StoreApiKey;
use App\Models\StoreUser;
use App\Policies\StoreApiKeyPolicy;
use PHPUnit\Framework\TestCase;

class StoreApiKeyPolicyTest extends TestCase
{
    private StoreApiKeyPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new StoreApiKeyPolicy();
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

    public function test_admin_can_view_any(): void
    {
        $this->assertTrue($this->policy->viewAny($this->makeAdmin()));
    }

    public function test_admin_can_create(): void
    {
        $this->assertTrue($this->policy->create($this->makeAdmin()));
    }

    public function test_admin_can_update(): void
    {
        $this->assertTrue($this->policy->update($this->makeAdmin(), new StoreApiKey()));
    }

    public function test_admin_can_delete(): void
    {
        $this->assertTrue($this->policy->delete($this->makeAdmin(), new StoreApiKey()));
    }
}
