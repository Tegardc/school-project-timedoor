<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ReviewRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['parent', 'student']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'reviewText' => ['required', 'string'],
            'rating' => ['required', 'integer', 'between:1,5'],
            // 'answer' => ['required', 'array','min:1'],
            // 'answer.*.questionId' => ['required', 'exists:questions,id'],
            // 'answer.*.score' => ['required', 'integer', 'between:1,5'],

        ];
        return $rules;
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $schoolDetailId = $this->route('schoolDetailId');
            $user = Auth::user();
            if ($schoolDetailId && $user) {
                $isStudent = $user->childSchoolDetails()->where('schoolDetailId', $schoolDetailId)->exists();
                if (!$isStudent) {
                    $validator->errors()->add('schoolDetailId', 'Youre Not registered in this school');
                }
            }
        });
    }
}
