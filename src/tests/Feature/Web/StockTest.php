<?php

namespace Tests\Feature\Web;

use App\Models\Book;
use App\Models\Stock;
use App\Models\Store;
use App\Models\StoreUser;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class StockTest extends TestCase
{
    use DatabaseTransactions;

    public function test_owner_can_view_stocks_index(): void
    {
        $owner = StoreUser::factory()->owner()->create();

        $this->actingAs($owner, 'web')
            ->get(route('stocks.index'))
            ->assertOk();
    }

    public function test_employee_can_view_stocks_index(): void
    {
        $employee = StoreUser::factory()->employee()->create();

        $this->actingAs($employee, 'web')
            ->get(route('stocks.index'))
            ->assertOk();
    }

    public function test_owner_can_create_stock(): void
    {
        $owner = StoreUser::factory()->owner()->create();
        $book  = Book::factory()->create();

        $this->actingAs($owner, 'web')
            ->post(route('stocks.store'), [
                'book_id'  => $book->id,
                'quantity' => 10,
            ])
            ->assertRedirect(route('stocks.index'));

        $this->assertDatabaseHas('stocks', [
            'store_id' => $owner->store_id,
            'book_id'  => $book->id,
            'quantity' => 10,
        ]);
    }

    public function test_owner_can_update_own_store_stock(): void
    {
        $owner = StoreUser::factory()->owner()->create();
        $stock = Stock::factory()->create(['store_id' => $owner->store_id]);

        $this->actingAs($owner, 'web')
            ->put(route('stocks.update', $stock), [
                'book_id'  => $stock->book_id,
                'quantity' => 99,
            ])
            ->assertRedirect(route('stocks.index'));

        $this->assertDatabaseHas('stocks', ['id' => $stock->id, 'quantity' => 99]);
    }

    public function test_owner_cannot_update_other_store_stock(): void
    {
        $owner      = StoreUser::factory()->owner()->create();
        $otherStore = Store::factory()->create();
        $stock      = Stock::factory()->create(['store_id' => $otherStore->id]);

        $this->actingAs($owner, 'web')
            ->put(route('stocks.update', $stock), [
                'book_id'  => $stock->book_id,
                'quantity' => 99,
            ])
            ->assertForbidden();
    }

    public function test_owner_can_delete_own_store_stock(): void
    {
        $owner = StoreUser::factory()->owner()->create();
        $stock = Stock::factory()->create(['store_id' => $owner->store_id]);

        $this->actingAs($owner, 'web')
            ->delete(route('stocks.destroy', $stock))
            ->assertRedirect(route('stocks.index'));

        $this->assertDatabaseMissing('stocks', ['id' => $stock->id]);
    }

    public function test_owner_cannot_delete_other_store_stock(): void
    {
        $owner      = StoreUser::factory()->owner()->create();
        $otherStore = Store::factory()->create();
        $stock      = Stock::factory()->create(['store_id' => $otherStore->id]);

        $this->actingAs($owner, 'web')
            ->delete(route('stocks.destroy', $stock))
            ->assertForbidden();
    }

    public function test_owner_can_view_create_form(): void
    {
        $owner = StoreUser::factory()->owner()->create();

        $this->actingAs($owner, 'web')
            ->get(route('stocks.create'))
            ->assertOk();
    }

    public function test_owner_can_view_edit_form_for_own_store_stock(): void
    {
        $owner = StoreUser::factory()->owner()->create();
        $stock = Stock::factory()->create(['store_id' => $owner->store_id]);

        $this->actingAs($owner, 'web')
            ->get(route('stocks.edit', $stock))
            ->assertOk();
    }

    public function test_owner_cannot_view_edit_form_for_other_store_stock(): void
    {
        $owner      = StoreUser::factory()->owner()->create();
        $otherStore = Store::factory()->create();
        $stock      = Stock::factory()->create(['store_id' => $otherStore->id]);

        $this->actingAs($owner, 'web')
            ->get(route('stocks.edit', $stock))
            ->assertForbidden();
    }

    public function test_owner_can_export_csv(): void
    {
        $owner = StoreUser::factory()->owner()->create();
        $book  = Book::factory()->create();
        Stock::factory()->create(['store_id' => $owner->store_id, 'book_id' => $book->id, 'quantity' => 5]);

        $response = $this->actingAs($owner, 'web')
            ->get(route('stocks.export'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString($book->title, $response->getContent());
    }

    public function test_employee_can_export_csv(): void
    {
        $employee = StoreUser::factory()->employee()->create();

        $this->actingAs($employee, 'web')
            ->get(route('stocks.export'))
            ->assertOk();
    }

    public function test_export_returns_only_own_store_stocks(): void
    {
        $owner      = StoreUser::factory()->owner()->create();
        $otherStore = Store::factory()->create();
        $ownBook    = Book::factory()->create(['title' => '自店舗の本']);
        $otherBook  = Book::factory()->create(['title' => '他店舗の本']);

        Stock::factory()->create(['store_id' => $owner->store_id, 'book_id' => $ownBook->id]);
        Stock::factory()->create(['store_id' => $otherStore->id, 'book_id' => $otherBook->id]);

        $response = $this->actingAs($owner, 'web')
            ->get(route('stocks.export'));

        $content = $response->getContent();
        $this->assertStringContainsString('自店舗の本', $content);
        $this->assertStringNotContainsString('他店舗の本', $content);
    }

    public function test_guest_cannot_access_stocks(): void
    {
        $this->get(route('stocks.index'))
            ->assertRedirect(route('login'));
    }
}
