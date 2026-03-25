<?php

namespace Tests\Unit\Policies;

use App\Models\Admin;
use App\Models\PurchaseHistory;
use App\Models\StoreUser;
use App\Policies\PurchaseHistoryPolicy;
use PHPUnit\Framework\TestCase;

class PurchaseHistoryPolicyTest extends TestCase
{
    private PurchaseHistoryPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new PurchaseHistoryPolicy();
    }

    private function makeAdmin(): Admin
    {
        $admin = new Admin();
        $admin->id = 1;
        return $admin;
    }

    private function makeStoreUser(int $id, int $storeId, string $role = 'owner'): StoreUser
    {
        $user = new StoreUser();
        $user->id = $id;
        $user->store_id = $storeId;
        $user->role = $role;
        return $user;
    }

    private function makePurchaseHistory(int $storeId): PurchaseHistory
    {
        $ph = new PurchaseHistory();
        $ph->store_id = $storeId;
        return $ph;
    }

    // before()

    public function test_before_returns_true_for_admin(): void
    {
        $this->assertTrue($this->policy->before($this->makeAdmin(), 'viewAny'));
    }

    public function test_before_returns_null_for_store_user(): void
    {
        $this->assertNull($this->policy->before($this->makeStoreUser(1, 1), 'viewAny'));
    }

    // viewAny

    public function test_owner_can_view_any_purchase_history(): void
    {
        $this->assertTrue($this->policy->viewAny($this->makeStoreUser(1, 1, 'owner')));
    }

    public function test_employee_can_view_any_purchase_history(): void
    {
        $this->assertTrue($this->policy->viewAny($this->makeStoreUser(1, 1, 'employee')));
    }

    // view

    public function test_owner_can_view_own_store_purchase_history(): void
    {
        $user = $this->makeStoreUser(1, 1, 'owner');
        $ph   = $this->makePurchaseHistory(1);
        $this->assertTrue($this->policy->view($user, $ph));
    }

    public function test_owner_cannot_view_other_store_purchase_history(): void
    {
        $user = $this->makeStoreUser(1, 1, 'owner');
        $ph   = $this->makePurchaseHistory(2);
        $this->assertFalse($this->policy->view($user, $ph));
    }

    public function test_employee_can_view_own_store_purchase_history(): void
    {
        $user = $this->makeStoreUser(1, 1, 'employee');
        $ph   = $this->makePurchaseHistory(1);
        $this->assertTrue($this->policy->view($user, $ph));
    }

    public function test_employee_cannot_view_other_store_purchase_history(): void
    {
        $user = $this->makeStoreUser(1, 1, 'employee');
        $ph   = $this->makePurchaseHistory(2);
        $this->assertFalse($this->policy->view($user, $ph));
    }

    // create

    public function test_owner_can_create_purchase_history(): void
    {
        $this->assertTrue($this->policy->create($this->makeStoreUser(1, 1, 'owner')));
    }

    public function test_employee_can_create_purchase_history(): void
    {
        $this->assertTrue($this->policy->create($this->makeStoreUser(1, 1, 'employee')));
    }

    // delete

    public function test_owner_can_delete_own_store_purchase_history(): void
    {
        $user = $this->makeStoreUser(1, 1, 'owner');
        $ph   = $this->makePurchaseHistory(1);
        $this->assertTrue($this->policy->delete($user, $ph));
    }

    public function test_owner_cannot_delete_other_store_purchase_history(): void
    {
        $user = $this->makeStoreUser(1, 1, 'owner');
        $ph   = $this->makePurchaseHistory(2);
        $this->assertFalse($this->policy->delete($user, $ph));
    }

    public function test_employee_cannot_delete_own_store_purchase_history(): void
    {
        $user = $this->makeStoreUser(1, 1, 'employee');
        $ph   = $this->makePurchaseHistory(1);
        $this->assertFalse($this->policy->delete($user, $ph));
    }

    public function test_employee_cannot_delete_other_store_purchase_history(): void
    {
        $user = $this->makeStoreUser(1, 1, 'employee');
        $ph   = $this->makePurchaseHistory(2);
        $this->assertFalse($this->policy->delete($user, $ph));
    }
}
