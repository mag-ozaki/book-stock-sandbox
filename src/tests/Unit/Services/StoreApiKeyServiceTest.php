<?php

namespace Tests\Unit\Services;

use App\Models\StoreApiKey;
use App\Repositories\StoreApiKeyRepository;
use App\Services\StoreApiKeyService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class StoreApiKeyServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_list_by_store_delegates_to_repository(): void
    {
        $keys = new Collection();

        $repo = Mockery::mock(StoreApiKeyRepository::class);
        $repo->shouldReceive('listByStore')->with(1)->once()->andReturn($keys);

        $this->assertSame($keys, (new StoreApiKeyService($repo))->listByStore(1));
    }

    public function test_issue_returns_plain_key_and_model(): void
    {
        $model = new StoreApiKey();

        $capturedData = null;
        $repo         = Mockery::mock(StoreApiKeyRepository::class);
        $repo->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function (array $data) use (&$capturedData) {
                $capturedData = $data;
                return $data['store_id'] === 1
                    && $data['name'] === 'レジ1'
                    && isset($data['key_hash'])
                    && $data['created_by'] === 2;
            }))
            ->andReturn($model);

        $result = (new StoreApiKeyService($repo))->issue(1, 2, ['name' => 'レジ1']);

        $this->assertArrayHasKey('plain', $result);
        $this->assertArrayHasKey('model', $result);
        $this->assertSame($model, $result['model']);
        // plain と key_hash が対応していること
        $this->assertEquals($capturedData['key_hash'], hash('sha256', $result['plain']));
    }

    public function test_issue_with_optional_fields(): void
    {
        $model = new StoreApiKey();

        $repo = Mockery::mock(StoreApiKeyRepository::class);
        $repo->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function (array $data) {
                return isset($data['allowed_ips']) && isset($data['expires_at']);
            }))
            ->andReturn($model);

        $result = (new StoreApiKeyService($repo))->issue(1, 2, [
            'name'        => 'レジ2',
            'allowed_ips' => ['192.168.1.1'],
            'expires_at'  => '2030-12-31 00:00:00',
        ]);

        $this->assertArrayHasKey('plain', $result);
    }

    public function test_toggle_delegates_to_repository(): void
    {
        $apiKey  = new StoreApiKey();
        $updated = new StoreApiKey();

        $repo = Mockery::mock(StoreApiKeyRepository::class);
        $repo->shouldReceive('update')
            ->with($apiKey, ['is_active' => false])
            ->once()
            ->andReturn($updated);

        $result = (new StoreApiKeyService($repo))->toggle($apiKey, false);

        $this->assertSame($updated, $result);
    }

    public function test_delete_delegates_to_repository(): void
    {
        $apiKey = new StoreApiKey();

        $repo = Mockery::mock(StoreApiKeyRepository::class);
        $repo->shouldReceive('delete')->with($apiKey)->once();

        (new StoreApiKeyService($repo))->delete($apiKey);
    }

    public function test_authenticate_returns_null_when_key_not_found(): void
    {
        $repo = Mockery::mock(StoreApiKeyRepository::class);
        $repo->shouldReceive('findByKeyHash')->once()->andReturn(null);

        $result = (new StoreApiKeyService($repo))->authenticate('invalid-key');

        $this->assertNull($result);
    }

    public function test_authenticate_returns_null_when_key_is_inactive(): void
    {
        $apiKey = Mockery::mock(StoreApiKey::class);
        $apiKey->shouldReceive('getAttribute')->with('is_active')->andReturn(false);

        $repo = Mockery::mock(StoreApiKeyRepository::class);
        $repo->shouldReceive('findByKeyHash')->once()->andReturn($apiKey);

        $result = (new StoreApiKeyService($repo))->authenticate('some-key');

        $this->assertNull($result);
    }

    public function test_authenticate_returns_null_when_key_is_expired(): void
    {
        $past = Carbon::create(2000, 1, 1, 0, 0, 0);

        $apiKey = Mockery::mock(StoreApiKey::class);
        $apiKey->shouldReceive('getAttribute')->with('is_active')->andReturn(true);
        $apiKey->shouldReceive('getAttribute')->with('expires_at')->andReturn($past);

        $repo = Mockery::mock(StoreApiKeyRepository::class);
        $repo->shouldReceive('findByKeyHash')->once()->andReturn($apiKey);

        $result = (new StoreApiKeyService($repo))->authenticate('some-key');

        $this->assertNull($result);
    }

    public function test_authenticate_updates_last_used_at_and_returns_fresh_key(): void
    {
        $plain = 'test-plain-key-12345678901234567890';
        $hash  = hash('sha256', $plain);
        $fresh = new StoreApiKey();

        $apiKey = Mockery::mock(StoreApiKey::class);
        $apiKey->shouldReceive('getAttribute')->with('is_active')->andReturn(true);
        $apiKey->shouldReceive('getAttribute')->with('expires_at')->andReturn(null);
        $apiKey->shouldReceive('fresh')->once()->andReturn($fresh);

        $repo = Mockery::mock(StoreApiKeyRepository::class);
        $repo->shouldReceive('findByKeyHash')->with($hash)->once()->andReturn($apiKey);
        $repo->shouldReceive('update')
            ->with($apiKey, Mockery::on(fn ($data) => isset($data['last_used_at'])))
            ->once()
            ->andReturn($apiKey);

        $result = (new StoreApiKeyService($repo))->authenticate($plain);

        $this->assertSame($fresh, $result);
    }

    public function test_authenticate_returns_fresh_key_when_expires_in_future(): void
    {
        $plain  = 'another-test-key-123456789012345678';
        $hash   = hash('sha256', $plain);
        $future = Carbon::create(2099, 12, 31, 0, 0, 0);
        $fresh  = new StoreApiKey();

        $apiKey = Mockery::mock(StoreApiKey::class);
        $apiKey->shouldReceive('getAttribute')->with('is_active')->andReturn(true);
        $apiKey->shouldReceive('getAttribute')->with('expires_at')->andReturn($future);
        $apiKey->shouldReceive('fresh')->once()->andReturn($fresh);

        $repo = Mockery::mock(StoreApiKeyRepository::class);
        $repo->shouldReceive('findByKeyHash')->with($hash)->once()->andReturn($apiKey);
        $repo->shouldReceive('update')->once()->andReturn($apiKey);

        $result = (new StoreApiKeyService($repo))->authenticate($plain);

        $this->assertSame($fresh, $result);
    }
}
