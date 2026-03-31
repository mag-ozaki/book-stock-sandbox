<?php

namespace Tests\Unit\Services;

use App\Models\Genre;
use App\Repositories\GenreRepository;
use App\Services\GenreService;
use Illuminate\Database\Eloquent\Collection;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class GenreServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_list_all_delegates_to_repository(): void
    {
        $genres = new Collection();

        $repo = Mockery::mock(GenreRepository::class);
        $repo->shouldReceive('all')->once()->andReturn($genres);

        $this->assertSame($genres, (new GenreService($repo))->listAll());
    }

    public function test_create_delegates_to_repository(): void
    {
        $genre = new Genre();
        $data  = ['name' => '技術書'];

        $repo = Mockery::mock(GenreRepository::class);
        $repo->shouldReceive('create')->with($data)->once()->andReturn($genre);

        $this->assertSame($genre, (new GenreService($repo))->create($data));
    }

    public function test_update_delegates_to_repository(): void
    {
        $genre   = new Genre();
        $updated = new Genre();
        $data    = ['name' => '小説'];

        $repo = Mockery::mock(GenreRepository::class);
        $repo->shouldReceive('update')->with($genre, $data)->once()->andReturn($updated);

        $this->assertSame($updated, (new GenreService($repo))->update($genre, $data));
    }

    public function test_delete_delegates_to_repository(): void
    {
        $genre = new Genre();

        $repo = Mockery::mock(GenreRepository::class);
        $repo->shouldReceive('delete')->with($genre)->once();

        (new GenreService($repo))->delete($genre);
    }
}
