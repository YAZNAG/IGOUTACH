<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SetCreditLimitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('customer.set_credit_limit') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'credit_limit' => ['required', 'numeric', 'min:0', 'max:99999999'],
        ];
    }
}
