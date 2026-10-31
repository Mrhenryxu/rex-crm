import { parsePhoneNumberFromString } from 'libphonenumber-js/min';
import type { CallOutcome, CallStatus } from '../types/contact';

const dateTimeFormatter = new Intl.DateTimeFormat(undefined, { dateStyle: 'medium', timeStyle: 'short' });

/** API timestamps are UTC ISO 8601; show them in the viewer's local time. */
export function formatDateTime(iso: string): string {
    return dateTimeFormatter.format(new Date(iso));
}

/** Readable international format for display, e.g. "+61 412 345 678". */
export function formatPhone(e164: string): string {
    return parsePhoneNumberFromString(e164)?.formatInternational() ?? e164;
}

export const OUTCOME_LABELS: Record<CallOutcome, string> = {
    answered: 'Answered',
    busy: 'Busy',
    no_answer: 'No answer',
    voicemail: 'Voicemail',
    failed: 'Failed',
};

export const OUTCOME_COLORS: Record<CallOutcome, string> = {
    answered: 'green',
    busy: 'orange',
    no_answer: 'gray',
    voicemail: 'blue',
    failed: 'red',
};

export const STATUS_LABELS: Record<CallStatus, string> = {
    pending: 'Pending',
    completed: 'Completed',
    failed: 'Processing failed',
};
