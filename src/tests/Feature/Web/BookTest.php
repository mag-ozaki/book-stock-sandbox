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

    public function test_can_create_book_with_26_digit_jan_code(): void
    {
        $owner = StoreUser::factory()->owner()->create();

        $this->actingAs($owner, 'web')
            ->post(route('books.store'), [
                'title'    => 'JAN Book',
                'author'   => 'Author',
                'jan_code' => '97840000000001920000000000',
            ])
            ->assertRedirect(route('books.index'));

        $this->assertDatabaseHas('books', ['jan_code' => '97840000000001920000000000']);
    }

    public function test_jan_code_with_25_digits_fails_validation(): void
    {
        $owner = StoreUser::factory()->owner()->create();

        $this->actingAs($owner, 'web')
            ->post(route('books.store'), [
                'title'    => 'JAN Book',
                'author'   => 'Author',
                'jan_code' => '9784000000000192000000000',
            ])
            ->assertSessionHasErrors('jan_code');
    }

    public function test_jan_code_with_27_digits_fails_validation(): void
    {
        $owner = StoreUser::factory()->owner()->create();

        $this->actingAs($owner, 'web')
            ->post(route('books.store'), [
                'title'    => 'JAN Book',
                'author'   => 'Author',
                'jan_code' => '978400000000019200000000000',
            ])
            ->assertSessionHasErrors('jan_code');
    }

    public function test_jan_code_with_non_numeric_characters_fails_validation(): void
    {
        $owner = StoreUser::factory()->owner()->create();

        $this->actingAs($owner, 'web')
            ->post(route('books.store'), [
                'title'    => 'JAN Book',
                'author'   => 'Author',
                'jan_code' => '9784000000000192000000000A',
            ])
            ->assertSessionHasErrors('jan_code');
    }

    public function test_can_create_book_with_null_jan_code(): void
    {
        $owner = StoreUser::factory()->owner()->create();

        $this->actingAs($owner, 'web')
            ->post(route('books.store'), [
                'title'    => 'No JAN Book',
                'author'   => 'Author',
                'jan_code' => null,
            ])
            ->assertRedirect(route('books.index'));

        $this->assertDatabaseHas('books', ['title' => 'No JAN Book', 'jan_code' => null]);
    }

    public function test_duplicate_jan_code_fails_validation(): void
    {
        $owner = StoreUser::factory()->owner()->create();
        Book::factory()->create(['jan_code' => '97840000000001920000000000']);

        $this->actingAs($owner, 'web')
            ->post(route('books.store'), [
                'title'    => 'Duplicate JAN Book',
                'author'   => 'Author',
                'jan_code' => '97840000000001920000000000',
            ])
            ->assertSessionHasErrors('jan_code');
    }

    public function test_update_excludes_own_jan_code_from_unique_check(): void
    {
        $owner = StoreUser::factory()->owner()->create();
        $book  = Book::factory()->create(['jan_code' => '97840000000001920000000000']);

        $this->actingAs($owner, 'web')
            ->put(route('books.update', $book), [
                'title'    => 'Updated Title',
                'author'   => 'Updated Author',
                'jan_code' => '97840000000001920000000000',
            ])
            ->assertRedirect(route('books.index'));

        $this->assertDatabaseHas('books', ['id' => $book->id, 'jan_code' => '97840000000001920000000000']);
    }
}
