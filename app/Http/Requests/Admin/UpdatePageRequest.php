<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Publishing is the only thing the admin may change about a page.
 *
 * Route, title, position and parent belong to the blueprint, so they are absent
 * from the rules and get dropped from the payload.
 */
class UpdatePageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'is_published' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['is_published' => 'estado de publicación'];
    }
}
