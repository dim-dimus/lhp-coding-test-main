// Events are stored as UTC unix timestamps. They're global, so we render each
// one in the viewer's own timezone via Intl, and surface the tz abbreviation so
// the displayed time is never ambiguous.

export interface EventDateTime {
    date: string; // e.g. "Sat, Jun 21, 2026"
    time: string; // e.g. "19:30"
    tz: string; // e.g. "GMT+2"
    iso: string; // machine-readable, for <time datetime>
}

export function eventDateTime(
    unixSeconds: number | null | undefined,
): EventDateTime | null {
    if (!unixSeconds) {
        return null;
    }

    const d = new Date(unixSeconds * 1000);

    const date = new Intl.DateTimeFormat(undefined, {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    }).format(d);

    const time = new Intl.DateTimeFormat(undefined, {
        hour: '2-digit',
        minute: '2-digit',
    }).format(d);

    const tz =
        new Intl.DateTimeFormat(undefined, { timeZoneName: 'short' })
            .formatToParts(d)
            .find((p) => p.type === 'timeZoneName')?.value ?? '';

    return { date, time, tz, iso: d.toISOString() };
}
