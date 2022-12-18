<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class QuestionRequest extends FormRequest
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
            'topics' => 'required|array',
            'title' => 'required|max:255',
            'body' => 'required',
            'topics.*' => 'integer',
            'files.*' => 'nullable|mimes:png,jpg,jpeg,mp4,3gpp|max:21000',
            'users.*' => 'nullable|integer'
        ];
    }
}
