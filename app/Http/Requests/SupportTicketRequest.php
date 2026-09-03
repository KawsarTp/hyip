<?php

namespace App\Http\Requests;

use App\SupportTicketCategory;
use App\SupportTicketPriority;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SupportTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:200'],
            'category' => ['required', 'string', 'max:40', 'in:'.implode(',', array_column(SupportTicketCategory::cases(), 'value'))],
            'priority' => ['required', 'string', 'max:20', 'in:'.implode(',', array_column(SupportTicketPriority::cases(), 'value'))],
            'description' => ['required', 'string', 'min:10', 'max:20000'],
            'attachment' => ['nullable', 'file', 'max:10240', 'mimes:jpg,jpeg,png,webp,pdf,txt,doc,docx'],
        ];
    }
}
