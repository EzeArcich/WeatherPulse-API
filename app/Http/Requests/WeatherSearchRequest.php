<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class WeatherSearchRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'city' => ['required','string','min:2','max:120'],
        ];
    }

    public function city(): string
    {
        return trim((string) $this->query('city'));
    }
}

