<?php

namespace App\Support;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TableAvailability
{
    public function all(): Collection
    {
        return $this->availableQuery()
            ->select('meja.id_meja', 'meja.nomor_meja')
            ->orderBy('meja.id_meja')
            ->get();
    }

    public function isAvailable(int $tableId): bool
    {
        return $this->availableQuery()
            ->where('meja.id_meja', $tableId)
            ->exists();
    }

    private function availableQuery(): Builder
    {
        return DB::table('meja')
            ->whereRaw('LOWER(meja.status_meja) = ?', ['tersedia']);
    }
}
