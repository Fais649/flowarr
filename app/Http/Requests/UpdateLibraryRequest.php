<?php

namespace App\Http\Requests;

use App\Concerns\LibraryValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLibraryRequest extends FormRequest
{
    use LibraryValidationRules;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->libraryRules();
    }
}
