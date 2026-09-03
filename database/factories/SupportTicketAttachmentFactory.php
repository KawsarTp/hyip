<?php

namespace Database\Factories;

use App\Models\SupportTicketAttachment;
use App\Models\SupportTicketMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SupportTicketAttachment> */
class SupportTicketAttachmentFactory extends Factory
{
    protected $model = SupportTicketAttachment::class;

    public function definition(): array
    {
        return [
            'support_ticket_message_id' => SupportTicketMessage::factory(),
            'uploaded_by_id' => UserFactory::new(),
            'disk' => 'local',
            'path' => 'support-attachments/'.$this->faker->uuid().'.pdf',
            'original_name' => 'support-document.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
        ];
    }
}
