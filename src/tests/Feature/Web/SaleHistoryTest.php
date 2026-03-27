<?php

namespace Tests\Feature\Web;

use App\Models\Book;
use App\Models\SaleHistory;
use App\Models\Store;
use App\Models\StoreUser;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SaleHistoryTest extends TestCase
{
    use DatabaseTransactions;

    private Store $store;
    private StoreUser $owner;
    private StoreUser $employee;
    private Book $book;
    private SaleHistory $saleHistory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store       = Store::factory()->create();
        $this->owner       = StoreUser::factory()->owner()->create(['store_id' => $this->store->id]);
        $this->employee    = StoreUser::factory()->employee()->create(['store_id' => $this->store->id]);
        $this->book        = Book::factory()->create();
        $this->saleHistory = SaleHistory::factory()->create([
            'store_id' => $this->store->id,
            'book_id'  => $this->book->id,
        ]);
    }

    public function test_guest_cannot_access_sale_histories_index(): void
    {
        $this->get(route('sale-histories.index'))
            ->assertRedirect(route('login'));
    }

    public function test_owner_can_view_sale_histories_index(): void
    {
        $this->actingAs($this->owner, 'web')
            ->get(route('sale-histories.index'))
            ->assertOk();
    }

    public function test_employee_can_view_sale_histories_index(): void
    {
        $this->actingAs($this->employee, 'web')
            ->get(route('sale-histories.index'))
            ->assertOk();
    }

    public function test_owner_can_view_own_store_sale_history_show(): void
    {
        $this->actingAs($this->owner, 'web')
            ->get(route('sale-histories.show', $this->saleHistory))
            ->assertOk();
    }

    public function test_owner_cannot_view_other_store_sale_history_show(): void
    {
        $otherStore      = Store::factory()->create();
        $otherSaleHistory = SaleHistory::factory()->create([
            'store_id' => $otherStore->id,
            'book_id'  => $this->book->id,
        ]);

        $this->actingAs($this->owner, 'web')
            ->get(route('sale-histories.show', $otherSaleHistory))
            ->assertNotFound();
    }

    public function test_employee_can_view_own_store_sale_history_show(): void
    {
        $this->actingAs($this->employee, 'web')
            ->get(route('sale-histories.show', $this->saleHistory))
            ->assertOk();
    }

    public function test_employee_cannot_view_other_store_sale_history_show(): void
    {
        $otherStore      = Store::factory()->create();
        $otherSaleHistory = SaleHistory::factory()->create([
            'store_id' => $otherStore->id,
            'book_id'  => $this->book->id,
        ]);

        $this->actingAs($this->employee, 'web')
            ->get(route('sale-histories.show', $otherSaleHistory))
            ->assertNotFound();
    }
}
