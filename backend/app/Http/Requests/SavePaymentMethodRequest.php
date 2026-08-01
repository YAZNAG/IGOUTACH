<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\Settings\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SavePaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('payment_method.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $method = $this->route('paymentMethod');
        $id = $method instanceof Model ? $method->getKey() : null;

        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('payment_methods', 'code')->ignore($id)],
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', 'string', Rule::in(PaymentMethod::TYPES)],
            'is_active' => ['sometimes', 'boolean'],
            'position' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
