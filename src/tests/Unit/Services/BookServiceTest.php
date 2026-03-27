<?php

namespace Tests\Unit\Services;

use App\Models\Book;
use App\Repositories\BookRepository;
use App\Services\BookService;
use Illuminate\Database\Eloquent\Collection;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class BookServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_list_all_delegates_to_repository(): void
    {
        $books = new Collection();

        $repo = Mockery::mock(BookRepository::class);
        $repo->shouldReceive('all')->once()->andReturn($books);

        $this->assertSame($books, (new BookService($repo))->listAll());
    }

    public function test_list_by_store_delegates_to_repository(): void
    {
        $books = new Collection();

        $repo = Mockery::mock(BookRepository::class);
        $repo->shouldReceive('allByStore')->with(1)->once()->andReturn($books);

        $this->assertSame($books, (new BookService($repo))->listByStore(1));
    }

    public function test_create_delegates_to_repository(): void
    {
        $book = new Book();
        $data = ['title' => 'Test', 'author' => 'Author'];

        $repo = Mockery::mock(BookRepository::class);
        $repo->shouldReceive('create')->with($data)->once()->andReturn($book);

        $this->assertSame($book, (new BookService($repo))->create($data));
    }

    public function test_update_delegates_to_repository(): void
    {
        $book    = new Book();
        $updated = new Book();
        $data    = ['title' => 'Updated'];

        $repo = Mockery::mock(BookRepository::class);
        $repo->shouldReceive('update')->with($book, $data)->once()->andReturn($updated);

        $this->assertSame($updated, (new BookService($repo))->update($book, $data));
    }

    public function test_delete_delegates_to_repository(): void
    {
        $book = new Book();

        $repo = Mockery::mock(BookRepository::class);
        $repo->shouldReceive('delete')->with($book)->once();

        (new BookService($repo))->delete($book);
    }

    public function test_find_by_jan_code_delegates_to_repository(): void
    {
        $book = new Book();

        $repo = Mockery::mock(BookRepository::class);
        $repo->shouldReceive('findByJanCode')
            ->with('97840000000001920000000000')
            ->once()
            ->andReturn($book);

        $result = (new BookService($repo))->findByJanCode('97840000000001920000000000');

        $this->assertSame($book, $result);
    }
}
