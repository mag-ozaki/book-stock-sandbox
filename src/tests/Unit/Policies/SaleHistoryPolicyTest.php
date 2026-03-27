<?php

namespace Tests\Unit\Policies;

use App\Models\Admin;
use App\Models\SaleHistory;
use App\Models\StoreUser;
use App\Policies\SaleHistoryPolicy;
use PHPUnit\Framework\TestCase;

class SaleHistoryPolicyTest extends TestCase
{
    private SaleHistoryPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new SaleHistoryPolicy();
    }

    private function makeAdmin(): Admin
    {
        $admin     = new Admin();
        $admin->id = 1;

        return $admin;
    }

    private function makeOwner(int $storeId = 1): StoreUser
    {
        $user           = new StoreUser();
        $user->id       = 1;
        $user->store_id = $storeId;
        $user->role     = 'owner';

        return $user;
    }

    private function makeEmployee(int $storeId = 1): StoreUser
    {
        $user           = new StoreUser();
        $user->id       = 2;
        $user->store_id = $storeId;
        $user->role     = 'employee';

        return $user;
    }

    private function makeSaleHistory(int $storeId = 1): SaleHistory
    {
        $history           = new SaleHistory();
        $history->id       = 1;
        $history->store_id = $storeId;

        return $history;
    }

    // before()

    public function test_before_returns_true_for_admin(): void
    {
        $this->assertTrue($this->policy->before($this->makeAdmin(), 'viewAny'));
    }

    public function test_before_returns_null_for_owner(): void
    {
        $this->assertNull($this->policy->before($this->makeOwner(), 'viewAny'));
    }

    public function test_before_returns_null_for_employee(): void
    {
        $this->assertNull($this->policy->before($this->makeEmployee(), 'viewAny'));
    }

    // viewAny

    public function test_owner_can_view_any_sale_history(): void
    {
        $this->assertTrue($this->policy->viewAny($this->makeOwner()));
    }

    public function test_employee_can_view_any_sale_history(): void
    {
        $this->assertTrue($this->policy->viewAny($this->makeEmployee()));
    }

    // view（自店舗）

    public function test_owner_can_view_own_store_sale_history(): void
    {
        $owner   = $this->makeOwner(storeId: 1);
        $history = $this->makeSaleHistory(storeId: 1);

        $this->assertTrue($this->policy->view($owner, $history));
    }

    public function test_employee_can_view_own_store_sale_history(): void
    {
        $employee = $this->makeEmployee(storeId: 1);
        $history  = $this->makeSaleHistory(storeId: 1);

        $this->assertTrue($this->policy->view($employee, $history));
    }

    // view（他店舗）

    public function test_owner_cannot_view_other_store_sale_history(): void
    {
        $owner   = $this->makeOwner(storeId: 1);
        $history = $this->makeSaleHistory(storeId: 2);

        $this->assertFalse($this->policy->view($owner, $history));
    }

    public function test_employee_cannot_view_other_store_sale_history(): void
    {
        $employee = $this->makeEmployee(storeId: 1);
        $history  = $this->makeSaleHistory(storeId: 2);

        $this->assertFalse($this->policy->view($employee, $history));
    }
}
