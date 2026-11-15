<?php

namespace App\Http\Requests\Collections;

use App\Models\Collection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCollectionRequest extends FormRequest
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
        /** @var Collection $collection */
        $collection = $this->route('collection');

        return [
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('collections')
                    ->where('user_id', $this->user()->id)
                    ->withoutTrashed()
                    ->ignore($collection->id),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
