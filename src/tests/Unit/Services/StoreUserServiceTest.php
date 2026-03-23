<?php

namespace Tests\Unit\Services;

use App\Models\StoreUser;
use App\Repositories\StoreUserRepository;
use App\Services\StoreUserService;
use Illuminate\Database\Eloquent\Collection;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class StoreUserServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_list_all_delegates_to_repository(): void
    {
        $users = new Collection();

        $repo = Mockery::mock(StoreUserRepository::class);
        $repo->shouldReceive('all')->once()->andReturn($users);

        $this->assertSame($users, (new StoreUserService($repo))->listAll());
    }

    public function test_list_by_store_delegates_to_repository(): void
    {
        $users = new Collection();

        $repo = Mockery::mock(StoreUserRepository::class);
        $repo->shouldReceive('allByStore')->with(1)->once()->andReturn($users);

        $this->assertSame($users, (new StoreUserService($repo))->listByStore(1));
    }

    public function test_create_delegates_to_repository(): void
    {
        $user = new StoreUser();
        $data = ['name' => 'Test', 'email' => 'test@example.com', 'password' => 'password', 'role' => 'employee', 'store_id' => 1];

        $repo = Mockery::mock(StoreUserRepository::class);
        $repo->shouldReceive('create')->with($data)->once()->andReturn($user);

        $this->assertSame($user, (new StoreUserService($repo))->create($data));
    }

    public function test_update_with_password_delegates_as_is(): void
    {
        $user    = new StoreUser();
        $updated = new StoreUser();
        $data    = ['name' => 'Updated', 'password' => 'newpassword'];

        $repo = Mockery::mock(StoreUserRepository::class);
        $repo->shouldReceive('update')->with($user, $data)->once()->andReturn($updated);

        $this->assertSame($updated, (new StoreUserService($repo))->update($user, $data));
    }

    public function test_update_with_empty_password_removes_password_key(): void
    {
        /** パスワード未入力（空文字）の場合は password キーを削除してから Repository に渡す */
        $user         = new StoreUser();
        $updated      = new StoreUser();
        $inputData    = ['name' => 'Updated', 'password' => ''];
        $expectedData = ['name' => 'Updated'];

        $repo = Mockery::mock(StoreUserRepository::class);
        $repo->shouldReceive('update')->with($user, $expectedData)->once()->andReturn($updated);

        (new StoreUserService($repo))->update($user, $inputData);
    }

    public function test_update_without_password_key_delegates_as_is(): void
    {
        $user    = new StoreUser();
        $updated = new StoreUser();
        $data    = ['name' => 'Updated'];

        $repo = Mockery::mock(StoreUserRepository::class);
        $repo->shouldReceive('update')->with($user, $data)->once()->andReturn($updated);

        (new StoreUserService($repo))->update($user, $data);
    }

    public function test_delete_delegates_to_repository(): void
    {
        $user = new StoreUser();

        $repo = Mockery::mock(StoreUserRepository::class);
        $repo->shouldReceive('delete')->with($user)->once();

        (new StoreUserService($repo))->delete($user);
    }
}
