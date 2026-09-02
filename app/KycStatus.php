<?php

namespace App;

enum KycStatus: string
{
    case NotStarted = 'not_started';
    case Draft = 'draft';
    case InReview = 'in_review';
    case Verified = 'verified';
    case Rejected = 'rejected';
    case ResubmissionRequired = 'resubmission_required';
}
