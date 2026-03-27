<?php

namespace Tests\Unit\Repositories;

use App\Models\Book;
use App\Models\Stock;
use App\Models\Store;
use App\Repositories\BookRepository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class BookRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    private BookRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new BookRepository();
    }

    public function test_all_returns_books_ordered_by_title(): void
    {
        Book::factory()->create(['title' => 'Z Book']);
        Book::factory()->create(['title' => 'A Book']);
        Book::factory()->create(['title' => 'M Book']);

        $result = $this->repo->all();

        $this->assertEquals('A Book', $result->first()->title);
        $this->assertEquals('Z Book', $result->last()->title);
    }

    public function test_all_by_store_returns_only_books_with_stocks_in_that_store(): void
    {
        $store      = Store::factory()->create();
        $otherStore = Store::factory()->create();

        $bookInStore      = Book::factory()->create();
        $bookInOtherStore = Book::factory()->create();
        $bookWithNoStock  = Book::factory()->create();

        Stock::factory()->create(['store_id' => $store->id, 'book_id' => $bookInStore->id]);
        Stock::factory()->create(['store_id' => $otherStore->id, 'book_id' => $bookInOtherStore->id]);

        $result = $this->repo->allByStore($store->id);

        $this->assertTrue($result->contains('id', $bookInStore->id));
        $this->assertFalse($result->contains('id', $bookInOtherStore->id));
        $this->assertFalse($result->contains('id', $bookWithNoStock->id));
    }

    public function test_find_or_fail_returns_book(): void
    {
        $book = Book::factory()->create();

        $result = $this->repo->findOrFail($book->id);

        $this->assertEquals($book->id, $result->id);
    }

    public function test_create_persists_book(): void
    {
        $data = ['title' => 'New Book', 'author' => 'Author', 'jan_code' => null, 'publisher' => null, 'price' => null];

        $book = $this->repo->create($data);

        $this->assertDatabaseHas('books', ['title' => 'New Book']);
        $this->assertEquals('New Book', $book->title);
    }

    public function test_update_persists_changes(): void
    {
        $book = Book::factory()->create(['title' => 'Old Title']);

        $updated = $this->repo->update($book, ['title' => 'New Title', 'author' => $book->author]);

        $this->assertEquals('New Title', $updated->title);
        $this->assertDatabaseHas('books', ['id' => $book->id, 'title' => 'New Title']);
    }

    public function test_delete_removes_book(): void
    {
        $book = Book::factory()->create();

        $this->repo->delete($book);

        $this->assertDatabaseMissing('books', ['id' => $book->id]);
    }

    public function test_find_by_jan_code_returns_book_when_found(): void
    {
        $book = Book::factory()->create(['jan_code' => '97840000000001920000000000']);

        $result = $this->repo->findByJanCode('97840000000001920000000000');

        $this->assertInstanceOf(Book::class, $result);
        $this->assertSame($book->id, $result->id);
    }

    public function test_find_by_jan_code_returns_null_when_not_found(): void
    {
        $result = $this->repo->findByJanCode('99999999999999999999999999');

        $this->assertNull($result);
    }
}
