<?php

namespace Tests\Unit\Repositories;

use App\Models\Genre;
use App\Repositories\GenreRepository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class GenreRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    private GenreRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new GenreRepository();
    }

    public function test_all_returns_genres_ordered_by_name(): void
    {
        Genre::factory()->create(['name' => 'ビジネス書']);
        Genre::factory()->create(['name' => 'SF']);
        Genre::factory()->create(['name' => '小説']);

        $result = $this->repo->all();

        $this->assertEquals('SF', $result->first()->name);
        $this->assertEquals('小説', $result->last()->name);
    }

    public function test_find_or_fail_returns_genre(): void
    {
        $genre = Genre::factory()->create();

        $result = $this->repo->findOrFail($genre->id);

        $this->assertEquals($genre->id, $result->id);
    }

    public function test_create_persists_genre(): void
    {
        $data = ['name' => 'テスト書籍'];

        $genre = $this->repo->create($data);

        $this->assertDatabaseHas('genres', ['name' => 'テスト書籍']);
        $this->assertEquals('テスト書籍', $genre->name);
    }

    public function test_update_persists_changes(): void
    {
        $genre = Genre::factory()->create(['name' => '旧ジャンル名']);

        $updated = $this->repo->update($genre, ['name' => '新ジャンル名']);

        $this->assertEquals('新ジャンル名', $updated->name);
        $this->assertDatabaseHas('genres', ['id' => $genre->id, 'name' => '新ジャンル名']);
    }

    public function test_delete_removes_genre(): void
    {
        $genre = Genre::factory()->create();

        $this->repo->delete($genre);

        $this->assertDatabaseMissing('genres', ['id' => $genre->id]);
    }
}
