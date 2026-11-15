<?php

namespace App\Http\Requests\Collections;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCollectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('collections')
                    ->where('user_id', $this->user()->id)
                    ->withoutTrashed(),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
