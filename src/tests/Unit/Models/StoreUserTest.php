<?php

namespace Tests\Unit\Models;

use App\Models\StoreUser;
use PHPUnit\Framework\TestCase;

class StoreUserTest extends TestCase
{
    public function test_is_owner_returns_true_for_owner(): void
    {
        $user = new StoreUser();
        $user->role = 'owner';

        $this->assertTrue($user->isOwner());
        $this->assertFalse($user->isEmployee());
    }

    public function test_is_employee_returns_true_for_employee(): void
    {
        $user = new StoreUser();
        $user->role = 'employee';

        $this->assertTrue($user->isEmployee());
        $this->assertFalse($user->isOwner());
    }
}
