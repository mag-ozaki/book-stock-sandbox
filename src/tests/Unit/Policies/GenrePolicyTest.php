<?php

namespace Tests\Unit\Policies;

use App\Models\Admin;
use App\Models\Genre;
use App\Models\StoreUser;
use App\Policies\GenrePolicy;
use PHPUnit\Framework\TestCase;

class GenrePolicyTest extends TestCase
{
    private GenrePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new GenrePolicy();
    }

    private function makeAdmin(): Admin
    {
        $admin = new Admin();
        $admin->id = 1;
        return $admin;
    }

    private function makeOwner(): StoreUser
    {
        $user = new StoreUser();
        $user->id = 1;
        $user->store_id = 1;
        $user->role = 'owner';
        return $user;
    }

    private function makeEmployee(): StoreUser
    {
        $user = new StoreUser();
        $user->id = 2;
        $user->store_id = 1;
        $user->role = 'employee';
        return $user;
    }

    // before()

    public function test_before_returns_true_for_admin(): void
    {
        $this->assertTrue($this->policy->before($this->makeAdmin(), 'viewAny'));
    }

    public function test_before_returns_null_for_store_user(): void
    {
        $this->assertNull($this->policy->before($this->makeOwner(), 'viewAny'));
    }

    // viewAny

    public function test_owner_can_view_any_genre(): void
    {
        $this->assertTrue($this->policy->viewAny($this->makeOwner()));
    }

    public function test_employee_can_view_any_genre(): void
    {
        $this->assertTrue($this->policy->viewAny($this->makeEmployee()));
    }

    // view

    public function test_owner_can_view_genre(): void
    {
        $this->assertTrue($this->policy->view($this->makeOwner(), new Genre()));
    }

    public function test_employee_can_view_genre(): void
    {
        $this->assertTrue($this->policy->view($this->makeEmployee(), new Genre()));
    }

    // create

    public function test_owner_can_create_genre(): void
    {
        $this->assertTrue($this->policy->create($this->makeOwner()));
    }

    public function test_employee_can_create_genre(): void
    {
        $this->assertTrue($this->policy->create($this->makeEmployee()));
    }

    // update

    public function test_owner_can_update_genre(): void
    {
        $this->assertTrue($this->policy->update($this->makeOwner(), new Genre()));
    }

    public function test_employee_can_update_genre(): void
    {
        $this->assertTrue($this->policy->update($this->makeEmployee(), new Genre()));
    }

    // delete

    public function test_owner_can_delete_genre(): void
    {
        $this->assertTrue($this->policy->delete($this->makeOwner(), new Genre()));
    }

    public function test_employee_can_delete_genre(): void
    {
        $this->assertTrue($this->policy->delete($this->makeEmployee(), new Genre()));
    }
}
