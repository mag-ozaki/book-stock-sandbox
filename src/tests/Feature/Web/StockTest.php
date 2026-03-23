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

    public function test_guest_cannot_access_stocks(): void
    {
        $this->get(route('stocks.index'))
            ->assertRedirect(route('login'));
    }
}
