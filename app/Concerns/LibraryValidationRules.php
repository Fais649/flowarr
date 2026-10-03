<?php

namespace App\Concerns;

trait LibraryValidationRules
{
    /**
     * @return array<string, mixed>
     */
    protected function libraryRules(): array
    {
        return [
            'base_path' => ['required', 'string', $this->existsAndReadable()],
            'original_handling' => ['sometimes', 'nullable', 'in:keep,immediate,idle,window'],
            'replacement_start' => ['nullable', 'required_if:original_handling,window', 'date_format:H:i'],
            'replacement_end' => ['nullable', 'required_if:original_handling,window', 'date_format:H:i', 'different:replacement_start'],
            'scan_interval' => ['required', 'integer', 'min:60'],
        ];
    }

    private function existsAndReadable(): callable
    {
        return function (string $attribute, mixed $value, callable $fail): void {
            if (! is_string($value) || ! is_dir($value) || ! is_readable($value)) {
                $fail('The selected directory does not exist or is not readable.');
            }
        };
    }
}
