<?php

namespace Database\Factories;

use App\Models\SupportTicket;
use App\SupportTicketCategory;
use App\SupportTicketPriority;
use App\SupportTicketStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SupportTicket> */
class SupportTicketFactory extends Factory
{
    protected $model = SupportTicket::class;

    public function definition(): array
    {
        return [
            'user_id' => UserFactory::new(),
            'ticket_number' => 'TKT-'.$this->faker->unique()->numerify('######'),
            'subject' => $this->faker->sentence(6),
            'category' => SupportTicketCategory::General,
            'priority' => SupportTicketPriority::Normal,
            'status' => SupportTicketStatus::Open,
            'description' => $this->faker->paragraph(),
            'sla_due_at' => now()->addHours(48),
            'last_reply_at' => now(),
        ];
    }
}
