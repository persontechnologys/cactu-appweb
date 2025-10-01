<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;

class UpdatePasswordRequest extends FormRequest
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
            'current_password' => 'required',
            'new_password' => 'required|string|min:8|confirmed',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'current_password.required' => 'La contraseña actual es obligatoria.',
            'new_password.required' => 'La nueva contraseña es obligatoria.',
            'new_password.min' => 'La nueva contraseña debe tener al menos 8 caracteres.',
            'new_password.confirmed' => 'La confirmación de la contraseña no coincide.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes(): array
    {
        return [
            'current_password' => 'contraseña actual',
            'new_password' => 'nueva contraseña',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (!Hash::check($this->current_password, $this->user()->password)) {
                $validator->errors()->add('current_password', 'La contraseña actual es incorrecta.');
            }
        });
    }
}
