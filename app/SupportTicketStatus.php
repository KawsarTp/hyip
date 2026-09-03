<?php

namespace App;

enum SupportTicketStatus: string
{
    case Open = 'open';
    case WaitingForUser = 'waiting_for_user';
    case WaitingForStaff = 'waiting_for_staff';
    case Escalated = 'escalated';
    case Resolved = 'resolved';
    case Closed = 'closed';
}
