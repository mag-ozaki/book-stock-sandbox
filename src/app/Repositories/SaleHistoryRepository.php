<?php

namespace App\Repositories;

use App\Models\SaleHistory;

class SaleHistoryRepository
{
    public function create(array $data): SaleHistory
    {
        return SaleHistory::create($data);
    }
}
