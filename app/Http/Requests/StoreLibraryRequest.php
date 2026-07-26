<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLibraryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'base_path' => ['required', 'string', $this->existsAndReadable()],
            'scan_interval' => ['required', 'integer', 'min:60'],
        ];
    }

    private function existsAndReadable(): callable
    {
        return function (string $attribute, mixed $value, callable $fail): void {
            if (! is_string($value)) {
                $fail('The selected directory does not exist or is not readable.');
            } elseif (! is_dir($value)) {
                $fail('The selected directory does not exist or is not readable.');
            } elseif (! is_readable($value)) {
                $fail('The selected directory does not exist or is not readable.');
            }
        };
    }
}
