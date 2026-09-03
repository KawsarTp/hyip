<?php

namespace App\Http\Requests\Admin;

use App\SupportTicketCategory;
use App\SupportTicketPriority;
use App\SupportTicketStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SupportTicketUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('support.manage') === true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'max:30', 'in:'.implode(',', array_column(SupportTicketStatus::cases(), 'value'))],
            'priority' => ['required', 'string', 'max:20', 'in:'.implode(',', array_column(SupportTicketPriority::cases(), 'value'))],
            'category' => ['required', 'string', 'max:40', 'in:'.implode(',', array_column(SupportTicketCategory::cases(), 'value'))],
            'assigned_to_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
