<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EliminarTokenFcmRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fcm_token' => 'required|string',
        ];
    }
}
