<?php

namespace App\Services;

use App\Models\SubDistrict;
use Illuminate\Support\Facades\DB;

class SubDistrictService
{

    public function store(array $validated): SubDistrict
    {
        return DB::transaction(function () use ($validated) {
            $subDistrict = SubDistrict::create($validated);
            return $subDistrict;
        });
    }
    public function update(array $validated, int $id): ?SubDistrict
    {
        return DB::transaction(function () use ($validated, $id) {
            $subDistrict = SubDistrict::find($id);
            if (!$subDistrict) {
                return null;
            }
            $subDistrict->update($validated);
            return $subDistrict;
        });
    }
}
