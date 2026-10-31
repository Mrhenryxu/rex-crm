import { Avatar, type AvatarProps } from '@mantine/core';

/** Up to two letters: the first letter of the first name and of the last name. */
export function initials(firstName: string, lastName: string): string {
    const first = Array.from(firstName.trim())[0] ?? '';
    const last = Array.from(lastName.trim())[0] ?? '';

    return `${first}${last}`.toLocaleUpperCase();
}

interface ContactAvatarProps extends Omit<AvatarProps, 'children' | 'name'> {
    firstName: string;
    lastName: string;
}

/** Decorative: the full name is always shown next to it, so it is hidden from assistive technology. */
export function ContactAvatar({ firstName, lastName, ...props }: ContactAvatarProps) {
    return (
        <Avatar name={`${firstName} ${lastName}`} color="initials" radius="xl" aria-hidden data-testid="contact-avatar" {...props}>
            {initials(firstName, lastName)}
        </Avatar>
    );
}
