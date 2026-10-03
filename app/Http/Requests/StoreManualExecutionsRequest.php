<?php

namespace App\Http\Requests;

use App\LibraryJobId;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreManualExecutionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'library_id' => ['required', 'integer', 'exists:libraries,id'],
            'job_id' => ['required', Rule::enum(LibraryJobId::class)],
            'files' => ['required', 'array', 'min:1', 'max:100'],
            'files.*' => ['required', 'string', 'distinct', 'max:4096'],
            'mode' => ['required', 'in:enqueue,now'],
        ];
    }
}
