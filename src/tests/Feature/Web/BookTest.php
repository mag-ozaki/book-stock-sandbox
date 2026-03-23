<?php

namespace Tests\Feature\Web;

use App\Models\Book;
use App\Models\StoreUser;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class BookTest extends TestCase
{
    use DatabaseTransactions;

    public function test_owner_can_view_books_index(): void
    {
        $owner = StoreUser::factory()->owner()->create();

        $this->actingAs($owner, 'web')
            ->get(route('books.index'))
            ->assertOk();
    }

    public function test_employee_can_view_books_index(): void
    {
        $employee = StoreUser::factory()->employee()->create();

        $this->actingAs($employee, 'web')
            ->get(route('books.index'))
            ->assertOk();
    }

    public function test_owner_can_create_book(): void
    {
        $owner = StoreUser::factory()->owner()->create();

        $this->actingAs($owner, 'web')
            ->post(route('books.store'), [
                'title'  => 'New Book',
                'author' => 'Author Name',
            ])
            ->assertRedirect(route('books.index'));

        $this->assertDatabaseHas('books', ['title' => 'New Book']);
    }

    public function test_employee_can_create_book(): void
    {
        $employee = StoreUser::factory()->employee()->create();

        $this->actingAs($employee, 'web')
            ->post(route('books.store'), [
                'title'  => 'Employee Book',
                'author' => 'Author Name',
            ])
            ->assertRedirect(route('books.index'));

        $this->assertDatabaseHas('books', ['title' => 'Employee Book']);
    }

    public function test_owner_can_update_book(): void
    {
        $owner = StoreUser::factory()->owner()->create();
        $book  = Book::factory()->create();

        $this->actingAs($owner, 'web')
            ->put(route('books.update', $book), [
                'title'  => 'Updated Title',
                'author' => 'Updated Author',
            ])
            ->assertRedirect(route('books.index'));

        $this->assertDatabaseHas('books', ['id' => $book->id, 'title' => 'Updated Title']);
    }

    public function test_employee_can_update_book(): void
    {
        $employee = StoreUser::factory()->employee()->create();
        $book     = Book::factory()->create();

        $this->actingAs($employee, 'web')
            ->put(route('books.update', $book), [
                'title'  => 'Employee Updated',
                'author' => 'Author',
            ])
            ->assertRedirect(route('books.index'));

        $this->assertDatabaseHas('books', ['id' => $book->id, 'title' => 'Employee Updated']);
    }

    public function test_owner_can_delete_book(): void
    {
        $owner = StoreUser::factory()->owner()->create();
        $book  = Book::factory()->create();

        $this->actingAs($owner, 'web')
            ->delete(route('books.destroy', $book))
            ->assertRedirect(route('books.index'));

        $this->assertDatabaseMissing('books', ['id' => $book->id]);
    }

    public function test_owner_can_view_create_form(): void
    {
        $owner = StoreUser::factory()->owner()->create();

        $this->actingAs($owner, 'web')
            ->get(route('books.create'))
            ->assertOk();
    }

    public function test_owner_can_view_edit_form(): void
    {
        $owner = StoreUser::factory()->owner()->create();
        $book  = Book::factory()->create();

        $this->actingAs($owner, 'web')
            ->get(route('books.edit', $book))
            ->assertOk();
    }

    public function test_guest_cannot_access_books(): void
    {
        $this->get(route('books.index'))
            ->assertRedirect(route('login'));
    }
}
