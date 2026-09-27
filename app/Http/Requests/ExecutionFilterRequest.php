<?php

namespace App\Http\Requests;

use App\ExecutionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExecutionFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', Rule::in(['all', ...array_column(ExecutionStatus::cases(), 'value')])],
            'library_id' => ['nullable', 'integer'],
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort' => ['nullable', 'string', 'in:created_at,status,file_path,started_at,finished_at'],
            'direction' => ['nullable', 'string', 'in:asc,desc'],
        ];
    }
}
