<?php

namespace Tests\Unit\Policies;

use App\Models\Admin;
use App\Models\Stock;
use App\Models\StoreUser;
use App\Policies\StockPolicy;
use PHPUnit\Framework\TestCase;

class StockPolicyTest extends TestCase
{
    private StockPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new StockPolicy();
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

    private function makeStock(int $storeId): Stock
    {
        $stock = new Stock();
        $stock->store_id = $storeId;
        return $stock;
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

    public function test_owner_can_view_any_stock(): void
    {
        $this->assertTrue($this->policy->viewAny($this->makeStoreUser(1, 1, 'owner')));
    }

    public function test_employee_can_view_any_stock(): void
    {
        $this->assertTrue($this->policy->viewAny($this->makeStoreUser(1, 1, 'employee')));
    }

    // view

    public function test_user_can_view_own_store_stock(): void
    {
        $user = $this->makeStoreUser(1, 1);
        $stock = $this->makeStock(1);
        $this->assertTrue($this->policy->view($user, $stock));
    }

    public function test_user_cannot_view_other_store_stock(): void
    {
        $user = $this->makeStoreUser(1, 1);
        $stock = $this->makeStock(2);
        $this->assertFalse($this->policy->view($user, $stock));
    }

    // create

    public function test_owner_can_create_stock(): void
    {
        $this->assertTrue($this->policy->create($this->makeStoreUser(1, 1, 'owner')));
    }

    public function test_employee_can_create_stock(): void
    {
        $this->assertTrue($this->policy->create($this->makeStoreUser(1, 1, 'employee')));
    }

    // update

    public function test_user_can_update_own_store_stock(): void
    {
        $user = $this->makeStoreUser(1, 1);
        $stock = $this->makeStock(1);
        $this->assertTrue($this->policy->update($user, $stock));
    }

    public function test_user_cannot_update_other_store_stock(): void
    {
        $user = $this->makeStoreUser(1, 1);
        $stock = $this->makeStock(2);
        $this->assertFalse($this->policy->update($user, $stock));
    }

    // delete

    public function test_user_can_delete_own_store_stock(): void
    {
        $user = $this->makeStoreUser(1, 1);
        $stock = $this->makeStock(1);
        $this->assertTrue($this->policy->delete($user, $stock));
    }

    public function test_user_cannot_delete_other_store_stock(): void
    {
        $user = $this->makeStoreUser(1, 1);
        $stock = $this->makeStock(2);
        $this->assertFalse($this->policy->delete($user, $stock));
    }
}
