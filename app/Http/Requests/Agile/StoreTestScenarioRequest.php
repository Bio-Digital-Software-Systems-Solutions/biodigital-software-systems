<?php

namespace App\Http\Requests\Agile;

use App\Models\Agile\TestScenario;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreTestScenarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', TestScenario::class);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'given' => ['nullable', 'string'],
            'when' => ['nullable', 'string'],
            'then' => ['nullable', 'string'],
            'free_form' => ['nullable', 'string'],
            'automated_test_ref' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $gherkin = array_filter([
                $this->input('given'),
                $this->input('when'),
                $this->input('then'),
            ]);
            $hasGherkin = count($gherkin) > 0;
            $hasFreeForm = ! empty($this->input('free_form'));

            if (! $hasGherkin && ! $hasFreeForm) {
                $v->errors()->add('free_form', __('agile.validation.test_scenario.shape_required'));
            }

            if ($hasGherkin && $hasFreeForm) {
                $v->errors()->add('free_form', __('agile.validation.test_scenario.shape_mutually_exclusive'));
            }
        });
    }
}
