<?php

namespace App\Http\Requests;

use App\LibraryJobId;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreWorkerRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'job_type' => ['required', 'string', 'in:'.implode(',', array_map(fn (LibraryJobId $id) => $id->value, LibraryJobId::cases()))],
            'concurrency' => ['required', 'integer', 'min:1', 'max:99'],
        ];
    }
}
