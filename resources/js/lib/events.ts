export function statusVariant(status: string): 'default' | 'destructive' | 'secondary' | 'outline' {
    switch (status) {
        case 'published':
            return 'default';
        case 'cancelled':
            return 'destructive';
        case 'sold_out':
            return 'secondary';
        default:
            return 'outline';
    }
}

export function priceLabel(min: number | null | undefined, currency: string): string {
    const price = Number(min ?? 0);
    if (!price) return 'Free';
    return `${currency} ${price.toFixed(0)}+`;
}
