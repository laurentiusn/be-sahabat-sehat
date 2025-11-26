<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CreateIndikatorRequest extends FormRequest
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
            'type'      => ['required', 'string', 'in:td,gd,imt'],
            'status'    => ['required', 'string'],
            'range'     => ['required', 'array'],
            'color_hex' => ['required', 'string', 'size:7'],
        ];
    }
    public function messages(): array
    {
        return [
            'type.in' => 'Tipe indikator harus salah satu dari: td, gd, atau imt.',
            'color_hex.regex' => 'Format warna harus kode Hex yang valid (contoh: #28a745).',
        ];
    }
}
