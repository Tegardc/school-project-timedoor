<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Requests\SubDistrictRequest;
use App\Http\Resources\SubDistrictResource;
use App\Models\SubDistrict;
use App\Services\SubDistrictService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubdistrictController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $subdistrict = SubDistrict::all();
            return ResponseHelper::success(SubDistrictResource::collection($subdistrict), 'Successfully Display Data');
        } catch (\Exception $e) {
            return ResponseHelper::serverError("Oops display subdistrict is failed ", $e, "[SUBDISTRICT INDEX]: ");
        }
        //
    }
    // SubDistrictController.php
    public function getByDistrict($districtId)
    {
        try {
            $subDistricts = SubDistrict::where('districtId', $districtId)->get();
            return ResponseHelper::success(SubDistrictResource::collection($subDistricts), 'Sub-districts retrieved');
        } catch (\Exception $e) {
            return ResponseHelper::serverError("Oops display subdistrict by district id is failed ", $e, "[SUBDISTRICT GETBYDISTRICT]: ");
        }
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SubDistrictRequest $request, SubDistrictService $service)
    {
        try {
            $validated = $request->validated();
            $subDistrict = $service->store($validated);
            DB::commit();
            return ResponseHelper::created(new SubDistrictResource($subDistrict), 'Created Successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::serverError("Oops created subdistrict is failed ", $e, "[SUBDISTRICT STORE]: ");
        }



        //
    }

    /**
     * Display the specified resource.
     */
    public function show(SubDistrict $subDistrict)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SubDistrict $subDistrict)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SubDistrictRequest $request, SubDistrictService $service, $id)
    {
        try {
            $validated = $request->validated();
            $subDistrict = $service->update($validated, $id);
            if (!$subDistrict) {
                return ResponseHelper::notFound('Data Not Found');
            }
            return ResponseHelper::success(new SubDistrictResource($subDistrict), 'Sub District Update Success');
        } catch (\Exception $e) {
            return ResponseHelper::serverError("Oops updated subdistrict is failed ", $e, "[SUBDISTRICT UPDATE]: ");
        }

        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            //code...
            $subDistrict = SubDistrict::find($id);
            if (!$subDistrict) {
                return ResponseHelper::notFound('Sub District not found');
            }

            $subDistrict->delete();

            return ResponseHelper::success(null, 'Sub District deleted successfully');
        } catch (\Exception $e) {
            return ResponseHelper::serverError("Oops deleted subdistrict is failed ", $e, "[SUBDISTRICT DESTROY]: ");
        }
        //
    }
}
