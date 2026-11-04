<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionWorkflowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'exists:customers,id'],
            'package_id' => ['required', 'exists:packages,id'],
            'addons' => ['nullable', 'array'],
            'addons.*.id' => ['required', 'exists:package_addons,id'],
            'addons.*.qty' => ['nullable', 'integer', 'min:1'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['required', 'string', 'in:cash,gcash,maya,bank_transfer,card'],
            'payment_status' => ['required', 'string', 'in:pending,completed'],
            'rfid' => ['nullable', 'string', 'max:100'],
            'rfid_scanned' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
            'photos' => ['required', 'array', 'min:1'],
            'photos.*.original_filename' => ['required', 'string'],
            'photos.*.preview_path' => ['required', 'string'],
            'photos.*.storage_path' => ['nullable', 'string'],
            'photos.*.is_starred' => ['nullable', 'boolean'],
            'photos.*.is_favorite' => ['nullable', 'boolean'],
        ];
    }
}

