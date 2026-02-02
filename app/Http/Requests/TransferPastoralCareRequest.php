<?php

namespace App\Http\Requests;

use App\Models\PastoralCare;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransferPastoralCareRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('transfer pastoral care');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $pastoralCare = $this->route('pastoralCare');
        $currentPastorId = $pastoralCare instanceof PastoralCare ? $pastoralCare->pastor_id : null;

        return [
            'transferred_to_id' => [
                'required',
                'integer',
                'exists:users,id',
                Rule::notIn([$currentPastorId]),
            ],
            'transfer_reason' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'transferred_to_id.required' => 'Veuillez sélectionner un pasteur/agent de destination.',
            'transferred_to_id.exists' => 'Le pasteur/agent sélectionné n\'existe pas.',
            'transferred_to_id.not_in' => 'Le rendez-vous est déjà assigné à ce pasteur/agent.',
            'transfer_reason.max' => 'La raison du transfert ne peut pas dépasser 1000 caractères.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'transferred_to_id' => 'pasteur/agent de destination',
            'transfer_reason' => 'raison du transfert',
        ];
    }
}
