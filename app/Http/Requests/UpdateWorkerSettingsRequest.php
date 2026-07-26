<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkerSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'concurrency.transcode_media' => 'required|integer|min:1',
            'concurrency.extract_subs' => 'required|integer|min:1',
            'concurrency.convert_sub' => 'required|integer|min:1',
            'paused' => 'required|boolean',
        ];
    }
}
