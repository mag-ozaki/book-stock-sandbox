<?php

namespace Tests\Feature\Web;

use App\Models\Book;
use App\Models\Genre;
use App\Models\StoreUser;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class GenreTest extends TestCase
{
    use DatabaseTransactions;

    // 未認証

    public function test_guest_cannot_access_genres_index(): void
    {
        $this->get(route('genres.index'))
            ->assertRedirect(route('login'));
    }

    // index

    public function test_owner_can_view_genres_index(): void
    {
        $owner = StoreUser::factory()->owner()->create();

        $this->actingAs($owner, 'web')
            ->get(route('genres.index'))
            ->assertOk();
    }

    public function test_employee_can_view_genres_index(): void
    {
        $employee = StoreUser::factory()->employee()->create();

        $this->actingAs($employee, 'web')
            ->get(route('genres.index'))
            ->assertOk();
    }

    // create

    public function test_owner_can_create_genre(): void
    {
        $owner = StoreUser::factory()->owner()->create();

        $this->actingAs($owner, 'web')
            ->post(route('genres.store'), ['name' => '技術書'])
            ->assertRedirect(route('genres.index'));

        $this->assertDatabaseHas('genres', ['name' => '技術書']);
    }

    public function test_employee_can_create_genre(): void
    {
        $employee = StoreUser::factory()->employee()->create();

        $this->actingAs($employee, 'web')
            ->post(route('genres.store'), ['name' => 'ビジネス書'])
            ->assertRedirect(route('genres.index'));

        $this->assertDatabaseHas('genres', ['name' => 'ビジネス書']);
    }

    // update

    public function test_owner_can_update_genre(): void
    {
        $owner = StoreUser::factory()->owner()->create();
        $genre = Genre::factory()->create(['name' => '旧ジャンル']);

        $this->actingAs($owner, 'web')
            ->put(route('genres.update', $genre), ['name' => '新ジャンル'])
            ->assertRedirect(route('genres.index'));

        $this->assertDatabaseHas('genres', ['id' => $genre->id, 'name' => '新ジャンル']);
    }

    public function test_employee_can_update_genre(): void
    {
        $employee = StoreUser::factory()->employee()->create();
        $genre    = Genre::factory()->create(['name' => '旧ジャンル']);

        $this->actingAs($employee, 'web')
            ->put(route('genres.update', $genre), ['name' => '更新ジャンル'])
            ->assertRedirect(route('genres.index'));

        $this->assertDatabaseHas('genres', ['id' => $genre->id, 'name' => '更新ジャンル']);
    }

    // delete

    public function test_owner_can_delete_genre(): void
    {
        $owner = StoreUser::factory()->owner()->create();
        $genre = Genre::factory()->create();

        $this->actingAs($owner, 'web')
            ->delete(route('genres.destroy', $genre))
            ->assertRedirect(route('genres.index'));

        $this->assertDatabaseMissing('genres', ['id' => $genre->id]);
    }

    public function test_employee_can_delete_genre(): void
    {
        $employee = StoreUser::factory()->employee()->create();
        $genre    = Genre::factory()->create();

        $this->actingAs($employee, 'web')
            ->delete(route('genres.destroy', $genre))
            ->assertRedirect(route('genres.index'));

        $this->assertDatabaseMissing('genres', ['id' => $genre->id]);
    }

    // バリデーション

    public function test_name_is_required(): void
    {
        $owner = StoreUser::factory()->owner()->create();

        $this->actingAs($owner, 'web')
            ->post(route('genres.store'), ['name' => ''])
            ->assertSessionHasErrors('name');
    }

    public function test_duplicate_name_fails_validation_on_create(): void
    {
        $owner = StoreUser::factory()->owner()->create();
        Genre::factory()->create(['name' => '小説']);

        $this->actingAs($owner, 'web')
            ->post(route('genres.store'), ['name' => '小説'])
            ->assertSessionHasErrors('name');
    }

    public function test_update_excludes_own_name_from_unique_check(): void
    {
        $owner = StoreUser::factory()->owner()->create();
        $genre = Genre::factory()->create(['name' => '小説']);

        $this->actingAs($owner, 'web')
            ->put(route('genres.update', $genre), ['name' => '小説'])
            ->assertRedirect(route('genres.index'));

        $this->assertDatabaseHas('genres', ['id' => $genre->id, 'name' => '小説']);
    }

    // ジャンル削除後の書籍への影響

    public function test_deleting_genre_sets_books_genre_id_to_null(): void
    {
        $owner = StoreUser::factory()->owner()->create();
        $genre = Genre::factory()->create();
        $book  = Book::factory()->create(['genre_id' => $genre->id]);

        $this->actingAs($owner, 'web')
            ->delete(route('genres.destroy', $genre))
            ->assertRedirect(route('genres.index'));

        $this->assertDatabaseHas('books', ['id' => $book->id, 'genre_id' => null]);
    }

    // フォームアクセス

    public function test_owner_can_access_create_form(): void
    {
        $owner = StoreUser::factory()->owner()->create();

        $this->actingAs($owner, 'web')
            ->get(route('genres.create'))
            ->assertOk();
    }

    public function test_edit_form_includes_genre_prop(): void
    {
        $owner = StoreUser::factory()->owner()->create();
        $genre = Genre::factory()->create();

        $this->actingAs($owner, 'web')
            ->get(route('genres.edit', $genre))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('genre'));
    }
}
