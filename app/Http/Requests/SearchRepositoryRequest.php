<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchRepositoryRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'max:256', 'min:1']
        ];
    }
    
    public function messages(): array
    {
        return [
            'q.required' => 'The query parameter \'q\' must be provided',
            'q.string' => 'The query parameter \'q\' must be a string',
            'q.max' => 'The query parameter \'q\' cannot be longer than 256 chars.',
            'q.min' => 'The query parameter \'q\' cannot be empty'
        ];
    }
}
