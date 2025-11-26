<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateIndikatorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type'      => ['sometimes', 'required', 'string', 'in:td,gd,imt'],
            'status'    => ['sometimes', 'required', 'string', 'max:100'],
            'range'     => ['sometimes', 'required', 'array'],
            'color_hex' => ['sometimes', 'required', 'string', 'regex:/^#([a-f0-9]{6}|[a-f0-9]{3})$/i'],
        ];
    }
}
