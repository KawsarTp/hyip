<?php

namespace App;

enum SupportTicketCategory: string
{
    case General = 'general';
    case Deposits = 'deposits';
    case Payouts = 'payouts';
    case Investments = 'investments';
    case Verification = 'verification';
    case Technical = 'technical';
    case Complaint = 'complaint';
}
