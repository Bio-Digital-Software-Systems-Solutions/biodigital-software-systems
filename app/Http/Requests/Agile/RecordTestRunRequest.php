<?php

namespace App\Http\Requests\Agile;

use Illuminate\Foundation\Http\FormRequest;

class RecordTestRunRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('recordRun', $this->route('scenario'));
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:passed,failed,blocked'],
            'failure_notes' => ['nullable', 'string', 'max:2000', 'required_if:status,failed'],
        ];
    }
}
