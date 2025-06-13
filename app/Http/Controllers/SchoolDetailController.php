<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Requests\SchoolDetailRequest;
use App\Http\Resources\SchoolDetailResource;
use App\Models\SchoolDetail;
use App\Models\SchoolGallery;
use App\Services\SchoolDetailService;
use Database\Seeders\SchoolDetailSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class SchoolDetailController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $schools = SchoolDetail::with(['schools', 'status', 'educationLevel', 'accreditation', 'schoolGallery'])->get();

            return ResponseHelper::success(SchoolDetailResource::collection($schools), 'Display Data Successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
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
    public function store(SchoolDetailRequest $request, SchoolDetailService $service)
    {
        try {
            $validated = $request->validated();
            $schoolDetail = $service->store($validated);
            return ResponseHelper::created(new SchoolDetailResource($schoolDetail), 'Created Success');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
        //
    }
    public function getBySubDistrict($id)
    {
        $schoolDetails = SchoolDetail::whereHas('schools', function ($query) use ($id) {
            $query->where('subDistrictId', $id);
        })->get();

        return ResponseHelper::success($schoolDetails, 'School details by sub-district retrieved');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $schools = SchoolDetail::with(['schools', 'status', 'educationLevel', 'accreditation', 'schoolGallery', 'reviews'])->find($id);
            if (!$schools) {
                return ResponseHelper::notFound('Data Not Found');
            }
            $totalReviews = $schools->reviews->count();
            $averageRating = round($schools->reviews->avg('rating'), 1);
            return ResponseHelper::success(new SchoolDetailResource($schools), 'Show Data Success');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SchoolDetail $SchoolDetail)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    // SchoolDetailController.php

    public function update(SchoolDetailRequest $request, $id, SchoolDetailService $service)
    {
        try {
            $validated = $request->validated();

            $school = $service->update($validated, $id);

            if (!$school) {
                return ResponseHelper::notFound('Data Not Found');
            }

            return ResponseHelper::success(
                new SchoolDetailResource($school),
                'Update Data Success'
            );
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }



        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $schools = SchoolDetail::find($id);
            if (!$schools) {
                return ResponseHelper::notFound('Data Not Found');
            }
            $schools->delete();
            return ResponseHelper::success('Deleted Successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
        //
    }
    public function filter(Request $request, SchoolDetailService $service)
    {
        $filters = $request->only([
            'provinceId',
            'districtId',
            'subDistrictId',
            'educationLevelId',
            'statusId',
            'accreditationId',
            'schoolId'
        ]);

        $data = $service->filter($filters);
        return ResponseHelper::success(SchoolDetailResource::collection($data), 'Filtered School Details');
    }

    public function ranking()
    {
        try {
            $schools = SchoolDetail::with(['schoolGallery', 'reviews'])->withCount(['reviews as total_reviews'])->withAvg('reviews as average_rating', 'rating')->orderByDesc('average_rating')->orderByDesc('total_reviews')->get();

            return ResponseHelper::success(SchoolDetailResource::collection($schools), 'Ranking By Rating & Reviewers');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
}
