<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportIcsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create appointments');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:2048',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $extension = strtolower((string) $value->getClientOriginalExtension());
                    if (! in_array($extension, ['ics', 'ical', 'txt'])) {
                        $fail('Le fichier doit être au format .ics, .ical ou .txt.');
                    }
                },
            ],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Le fichier iCalendar est obligatoire.',
            'file.file' => 'Le fichier doit être un fichier valide.',
            'file.max' => 'Le fichier ne peut pas dépasser 2 Mo.',
        ];
    }
}
