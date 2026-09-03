<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InvestmentPlanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->canAccessAdminArea() === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:140', 'alpha_dash', Rule::unique('investment_plans', 'slug')->ignore($this->route('investmentPlan'))],
            'description' => ['required', 'string', 'max:5000'],
            'currency' => ['required', 'string', 'size:3', 'uppercase', Rule::exists('currencies', 'code')->where('is_enabled', true)],
            'risk_rating' => ['required', 'string', 'max:30'],
            'minimum_amount' => ['required', 'regex:/^\d{1,12}(\.\d{1,2})?$/'],
            'maximum_amount' => ['nullable', 'regex:/^\d{1,12}(\.\d{1,2})?$/'],
            'capacity' => ['nullable', 'regex:/^\d{1,12}(\.\d{1,2})?$/'],
            'term_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'lock_days' => ['required', 'integer', 'min:0', 'max:3650'],
            'projected_rate' => ['required', 'regex:/^\d{1,3}(\.\d{1,2})?$/', 'numeric', 'min:0', 'max:100'],
            'rate_type' => ['required', Rule::in(['annual_simple', 'term_simple'])],
            'calculation_frequency' => ['required', Rule::in(['daily', 'monthly', 'maturity'])],
            'day_count_basis' => ['required', Rule::in(['360', '365'])],
            'status' => ['required', Rule::in(['draft', 'published', 'paused', 'retired'])],
            'sort_order' => ['required', 'integer', 'min:0', 'max:10000'],
        ];
    }
}
