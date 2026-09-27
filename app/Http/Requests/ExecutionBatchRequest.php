<?php

namespace App\Http\Requests;

use App\Models\Execution;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;

class ExecutionBatchRequest extends FormRequest
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
            'ids' => ['present', 'array', 'max:1000'],
            'ids.*' => ['integer'],
        ];
    }

    /**
     * @return Builder<Execution>
     */
    public function executions(): Builder
    {
        return Execution::whereIn('id', $this->validated('ids', []));
    }
}
