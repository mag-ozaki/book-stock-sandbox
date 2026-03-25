<?php

namespace Tests\Feature\Web;

use App\Models\Book;
use App\Models\PurchaseHistory;
use App\Models\Store;
use App\Models\StoreUser;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PurchaseHistoryTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guest_cannot_access_purchase_histories(): void
    {
        $this->get(route('purchase-histories.index'))
            ->assertRedirect(route('login'));
    }

    public function test_owner_can_view_purchase_histories_index(): void
    {
        $owner = StoreUser::factory()->owner()->create();

        $this->actingAs($owner, 'web')
            ->get(route('purchase-histories.index'))
            ->assertOk();
    }

    public function test_employee_can_view_purchase_histories_index(): void
    {
        $employee = StoreUser::factory()->employee()->create();

        $this->actingAs($employee, 'web')
            ->get(route('purchase-histories.index'))
            ->assertOk();
    }

    public function test_owner_can_view_create_form(): void
    {
        $owner = StoreUser::factory()->owner()->create();

        $this->actingAs($owner, 'web')
            ->get(route('purchase-histories.create'))
            ->assertOk();
    }

    public function test_owner_can_store_purchase_history(): void
    {
        $owner = StoreUser::factory()->owner()->create();
        $book  = Book::factory()->create();

        $this->actingAs($owner, 'web')
            ->post(route('purchase-histories.store'), [
                'book_id'      => $book->id,
                'quantity'     => 3,
                'purchased_at' => '2025-01-15',
                'note'         => 'テスト備考',
            ])
            ->assertRedirect(route('purchase-histories.index'));

        $this->assertDatabaseHas('purchase_histories', [
            'store_id'      => $owner->store_id,
            'store_user_id' => $owner->id,
            'book_id'       => $book->id,
            'quantity'      => 3,
        ]);
    }

    public function test_employee_can_store_purchase_history(): void
    {
        $employee = StoreUser::factory()->employee()->create();
        $book     = Book::factory()->create();

        $this->actingAs($employee, 'web')
            ->post(route('purchase-histories.store'), [
                'book_id'      => $book->id,
                'quantity'     => 1,
                'purchased_at' => '2025-01-15',
            ])
            ->assertRedirect(route('purchase-histories.index'));
    }

    public function test_owner_can_view_own_store_purchase_history(): void
    {
        $owner = StoreUser::factory()->owner()->create();
        $book  = Book::factory()->create();
        $ph    = PurchaseHistory::factory()->create([
            'store_id'      => $owner->store_id,
            'store_user_id' => $owner->id,
            'book_id'       => $book->id,
        ]);

        $this->actingAs($owner, 'web')
            ->get(route('purchase-histories.show', $ph))
            ->assertOk();
    }

    public function test_employee_cannot_view_other_store_purchase_history(): void
    {
        $employee   = StoreUser::factory()->employee()->create();
        $otherStore = Store::factory()->create();
        $otherOwner = StoreUser::factory()->owner()->create(['store_id' => $otherStore->id]);
        $book       = Book::factory()->create();
        $ph         = PurchaseHistory::factory()->create([
            'store_id'      => $otherStore->id,
            'store_user_id' => $otherOwner->id,
            'book_id'       => $book->id,
        ]);

        $this->actingAs($employee, 'web')
            ->get(route('purchase-histories.show', $ph))
            ->assertNotFound();
    }

    public function test_owner_can_delete_own_store_purchase_history(): void
    {
        $owner = StoreUser::factory()->owner()->create();
        $book  = Book::factory()->create();
        $ph    = PurchaseHistory::factory()->create([
            'store_id'      => $owner->store_id,
            'store_user_id' => $owner->id,
            'book_id'       => $book->id,
        ]);

        $this->actingAs($owner, 'web')
            ->delete(route('purchase-histories.destroy', $ph))
            ->assertRedirect(route('purchase-histories.index'));

        $this->assertDatabaseMissing('purchase_histories', ['id' => $ph->id]);
    }

    public function test_employee_cannot_delete_own_store_purchase_history(): void
    {
        $employee = StoreUser::factory()->employee()->create();
        $book     = Book::factory()->create();
        $ph       = PurchaseHistory::factory()->create([
            'store_id'      => $employee->store_id,
            'store_user_id' => $employee->id,
            'book_id'       => $book->id,
        ]);

        $this->actingAs($employee, 'web')
            ->delete(route('purchase-histories.destroy', $ph))
            ->assertForbidden();
    }

    public function test_owner_cannot_delete_other_store_purchase_history(): void
    {
        $owner      = StoreUser::factory()->owner()->create();
        $otherStore = Store::factory()->create();
        $otherOwner = StoreUser::factory()->owner()->create(['store_id' => $otherStore->id]);
        $book       = Book::factory()->create();
        $ph         = PurchaseHistory::factory()->create([
            'store_id'      => $otherStore->id,
            'store_user_id' => $otherOwner->id,
            'book_id'       => $book->id,
        ]);

        $this->actingAs($owner, 'web')
            ->delete(route('purchase-histories.destroy', $ph))
            ->assertNotFound();
    }
}
