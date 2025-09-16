<?php

namespace App\Services;

use App\Models\Review;
use App\Models\ReviewDetail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class ReviewService extends BaseService
{
    public function __construct()
    {
        $this->modelClass = Review::class;
    }
    public function getReview($id)
    {
        return Review::where('schoolId', $id)->get();
    }
    public function getReviewDetail($id)
    {
        return ReviewDetail::where('reviewId', $id)->get();
    }

    public function getAll($schoolDetailId, $perPage = null)
{
    $review = Review::select([
            'id',
            'reviewText',
            'rating',
            'userId',
            'schoolDetailId',
            'createdAt',
            'updatedAt'
        ])
        ->where('schoolDetailId', $schoolDetailId)
        ->where('status', Review::STATUS_APPROVED)
        ->with([
            'users:id,username,image',
            'schoolDetails:id,name',
            'reviewDetails' => function ($q) {
                $q->with('question:id,question');
            }
        ]);

    return $review->paginate($perPage ?? 10);
}
    public function approve($id)
    {
        $review = Review::find($id);
        if(!$review){
            return false;
        }
        return Review::where('id', $id)->update(['status' => Review::STATUS_APPROVED]);
    }
    public function rejected($id)
    {
        $review = Review::find($id);
        if(!$review){
            return false;
        }
        return Review::where('id', $id)->update(['status' => Review::STATUS_REJECTED]);
    }
    public function store(array $validated): Review
    {
        return DB::transaction(function () use ($validated) {
            $review = Review::create($validated);
            return $review;
        });
    }
    public function createOrUpdate(array $data, int $schoolDetailId): Review
{
    $userId     = Auth::id();
    $details    = $data['details'];
    $reviewText = $data['reviewText'] ?? null;

    // Hitung rating dari details (rata-rata score semua pertanyaan)
    $totalScore = array_sum(array_column($details, 'score'));
    $rating     = round($totalScore / count($details), 2);

    return DB::transaction(function () use ($userId, $schoolDetailId, $reviewText, $details, $rating) {
        $review = Review::where('userId', $userId)
            ->where('schoolDetailId', $schoolDetailId)
            ->first();

        if ($review) {
            // Update review yang sudah ada
            $review->update([
                'reviewText' => $reviewText,
                'rating'     => $rating,  // update rating juga
                'status'     => Review::STATUS_PENDING
            ]);
            $review->reviewDetails()->delete();
        } else {
            // Buat review baru
            $review = Review::create([
                'reviewText'     => $reviewText,
                'rating'         => $rating,
                'userId'         => $userId,
                'schoolDetailId' => $schoolDetailId,
                'status'         => Review::STATUS_PENDING
            ]);
        }

        // Simpan detail setiap pertanyaan
        foreach ($details as $detail) {
            ReviewDetail::create([
                'reviewId'   => $review->id,
                'questionId' => $detail['questionId'],
                'score'      => $detail['score'],
            ]);
        }

        return $review->load(['reviewDetails.question']);
    });
}

public function getRecentReview($limit = 5)
    {
        return Review::select([
            'id',
            'reviewText',
            'rating',
            'userId',
            'schoolDetailId',
            'createdAt'
        ])
        ->where('status', Review::STATUS_APPROVED)
        ->with([
            'users:id,username,image',
            'schoolDetails:id,name',
            'reviewDetails' => function ($q) {
                $q->with('question:id,question');
            }
        ])
        ->orderByDesc('createdAt')
        ->limit($limit)
        ->get();
    }

    public function AllReview(array $filters = [], $perPage = 10)
    {
        $query =  Review::with([
            'users:id,username,image',
            'schoolDetails:id,name',
            'reviewDetails' => function ($q) {
                $q->with('question:id,question');
            }
        ])->where('status', Review::STATUS_APPROVED);

        $query->when($filters, function ($query) use ($filters) {
            $this->applyFilters($query, $filters);
        });

        return $query->paginate($perPage);
    }
    private function applyFilters($query, array $filters)
{
    if (!empty($filters['search'])) {
        $query->where(function ($q) use ($filters) {
            $q->whereHas('schoolDetails', function ($q2) use ($filters) {
                $q2->where('name', 'like', '%' . $filters['search'] . '%')
                   ->orWhere('institutionCode', 'like', '%' . $filters['search'] . '%');
            })->orWhereHas('users', function ($q3) use ($filters) {
                $q3->where('username', 'like', '%' . $filters['search'] . '%');
            });
        });
    }

    if (!empty($filters['provinceName'])) {
        $query->whereHas('schoolDetails.schools.province', function ($q) use ($filters) {
            $q->where('name', 'like', '%' . $filters['provinceName'] . '%');
        });
    }

    if (!empty($filters['districtName'])) {
        $query->whereHas('schoolDetails.schools.district', function ($q) use ($filters) {
            $q->where('name', 'like', '%' . $filters['districtName'] . '%');
        });
    }

    if (!empty($filters['subDistrictName'])) {
        $query->whereHas('schoolDetails.schools.subDistrict', function ($q) use ($filters) {
            $q->where('name', 'like', '%' . $filters['subDistrictName'] . '%');
        });
    }

    if (!empty($filters['educationLevelName'])) {
        $query->whereHas('schoolDetails.educationLevel', function ($q) use ($filters) {
            $q->where('name', 'like', '%' . $filters['educationLevelName'] . '%');
        });
    }

    if (!empty($filters['statusName'])) {
        $query->whereHas('schoolDetails.status', function ($q) use ($filters) {
            $q->where('name', 'like', '%' . $filters['statusName'] . '%');
        });
    }

    if (!empty($filters['accreditationCode'])) {
        $query->whereHas('schoolDetails.accreditation', function ($q) use ($filters) {
            $q->where('code', 'like', '%' . $filters['accreditationCode'] . '%');
        });
    }

    if (!empty($filters['minRating'])) {
        $query->where('rating', '>=', $filters['minRating']);
    }

    if (!empty($filters['maxRating'])) {
        $query->where('rating', '<=', $filters['maxRating']);
    }

    if (!empty($filters['sortBy'])) {
        $sortField = $filters['sortBy'];
        $sortDirection = $filters['sortDirection'] ?? 'asc';

        $allowedSortFields = [
            'rating',
            'createdAt',
            'updatedAt'
        ];

        if (in_array($sortField, $allowedSortFields)) {
            $query->orderBy($sortField, $sortDirection);
        }
    } else {
        $query->orderByDesc('createdAt');
    }

    return $query;
}
public function getSchoolReviewsWithRating(int $schoolDetailId)
{
    // Ambil semua review untuk sekolah tertentu
    $reviews = Review::with([
        'users:id,email,image',
        'reviewDetails:id,reviewId,questionId,score'
    ])
        ->where('schoolDetailId', $schoolDetailId)
        ->where('status', Review::STATUS_APPROVED)
        ->get();

    // Hitung rata-rata per pertanyaan
    $questionAverages = ReviewDetail::select('questionId', DB::raw('AVG(score) as avg_score'))
        ->whereIn('reviewId', $reviews->pluck('id'))
        ->groupBy('questionId')
        ->get();

    // Hitung final rating (rata-rata dari semua avg_score)
    $finalRating = $questionAverages->avg('avg_score');

    return [
        'reviews' => $reviews,
        'questionAverages' => $questionAverages,
        'finalRating' => round($finalRating, 2)
    ];
}


}
