<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProcessingSettingsRequest extends FormRequest
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
            'window_enabled' => ['required', 'boolean'],
            'window_start' => ['required_if_accepted:window_enabled', 'nullable', 'date_format:H:i', 'different:window_end'],
            'window_end' => ['required_if_accepted:window_enabled', 'nullable', 'date_format:H:i'],
            'stream_timeout_minutes' => ['required', 'integer', 'min:5', 'max:1440'],
            'webhook_url' => ['nullable', 'url:http,https', 'max:2048'],
            'notify_on_completed' => ['required', 'boolean'],
            'notify_on_failed' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'window_start.different' => 'The processing window must start and end at different times.',
        ];
    }
}
