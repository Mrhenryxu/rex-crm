<?php

namespace App\Features\Contacts\Enums;

/**
 * Processing lifecycle of a call. "Failed" means processing failed after retries;
 * the result of a completed call is its CallOutcome.
 */
enum CallStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Failed = 'failed';
}
