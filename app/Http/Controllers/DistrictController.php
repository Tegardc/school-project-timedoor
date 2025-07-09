<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Requests\DistrictRequest;
use App\Http\Resources\DistrictResource;
use App\Models\District;
use App\Services\DistrictService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DistrictController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, DistrictService $service)
    {
        try {
            $provinceName = $request->query('provinceName');
            if (!$provinceName) {
                return ResponseHelper::notFound('Province Name Not Found');
            }
            $district = $service->getByProvince($provinceName);
            return ResponseHelper::success(DistrictResource::collection($district), 'Successfully Display Data');
        } catch (\Exception $e) {
            return ResponseHelper::serverError("Oops display district is failed ", $e, "[DISTRICT INDEX]: ");
        }

        //
    }
    public function getByProvince($provinceId)
    {
        try {
            //code...
            $districts = District::where('provinceId', $provinceId)->get();
            return ResponseHelper::success(DistrictResource::collection($districts), 'Districts retrieved');
        } catch (\Exception $e) {
            return ResponseHelper::serverError("Oops display district by province id is failed ", $e, "[DISTRICT GETBYPROVINCE]: ");
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
    public function store(DistrictRequest $request, DistrictService $service)
    {
        try {
            $validated = $request->validated();
            $district = $service->store($validated);
            DB::commit();
            return ResponseHelper::created(new DistrictResource($district), 'Created Successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::serverError("Oops create district is failed ", $e, "[DISTRICT STORE]: ");
        }

        //
    }

    /**
     * Display the specified resource.
     */
    public function show(District $district)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(District $district)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(DistrictRequest $request, DistrictService $service, $id)
    {
        try {
            $validated = $request->validated();
            $district = $service->update($validated, $id);
            if (!$district) {
                return ResponseHelper::notFound('Data Not Found');
            }
            return ResponseHelper::success(new DistrictResource($district), 'District Update Success');
        } catch (\Exception $e) {
            return ResponseHelper::serverError("Oops update district is failed ", $e, "[DISTRICT UPDATE]: ");
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DistrictService $service, $id)
    {
        try {
            //code...
            $district = District::find($id);
            if (!$district) {
                return ResponseHelper::notFound('District not found');
            }

            $service->softDelete($id);

            return ResponseHelper::success([], 'District deleted successfully');
        } catch (\Exception $e) {
            return ResponseHelper::serverError("Oops delete district is failed ", $e, "[DISTRICT DESTROY]: ");
        }
    }
    public function trash(DistrictService $service) {
        try {
            $district = $service->trash();
            return ResponseHelper::success(DistrictResource::collection($district), 'District trashed items retrieved successfully');
        } catch (\Exception $e) {
            return ResponseHelper::serverError("Oops display district is failed ", $e, "[DISTRICT TRASH]: ");
        }
    }
    public function restore(DistrictService $service, $id) {
        try {
            $district = $service->restore($id);
            return ResponseHelper::success(new DistrictResource($district), 'District restored successfully');
        } catch (\Exception $e) {
            return ResponseHelper::serverError("Oops restore district is failed ", $e, "[DISTRICT RESTORE]: ");
        }
    }
}
