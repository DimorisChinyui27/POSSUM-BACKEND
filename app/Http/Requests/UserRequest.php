<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'about' => 'nullable',
            'headline' => 'nullable',
            'gender' => [Rule::in(['Male', "Female"])],
            'topics' => 'nullable|array',
            'country_id' => 'required',
            'city_id' => 'nullable|integer',
            'dob' => 'date|required',
            'address' => 'nullable',
            'topics.*' => 'nullable|integer'
        ];
    }
}
