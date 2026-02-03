<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CityStoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required','string','min:2','max:120'],
        ];
    }

    public function name(): string
    {
        return trim((string) $this->input('name'));
    }
}

