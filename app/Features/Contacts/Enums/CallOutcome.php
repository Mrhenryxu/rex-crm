<?php

namespace App\Features\Contacts\Enums;

/**
 * Mocked result of a placed call.
 */
enum CallOutcome: string
{
    case Answered = 'answered';
    case Busy = 'busy';
    case NoAnswer = 'no_answer';
    case Voicemail = 'voicemail';
    case Failed = 'failed';
}
