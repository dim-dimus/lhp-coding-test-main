<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import EventRegisterDialog from '@/components/EventRegisterDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { eventDateTime } from '@/lib/datetime';

interface EventCard {
    id: string;
    name: string | null;
    description: string | null;
    type: string;
    status: string;
    created_time: number | null;
    city: string | null;
    country: string | null;
    address: string | null;
    images: string[];
    price: { min: number | null; currency: string };
}

const props = defineProps<{
    filters: {
        status: string | null;
        from: string | null;
        to: string | null;
        city: string | null;
    };
    statuses: string[];
    cities: string[];
}>();

const today = new Date().toISOString().slice(0, 10);

const form = reactive({
    status: props.filters.status ?? '',
    from: props.filters.from ?? today,
    to: props.filters.to ?? '',
    city: props.filters.city ?? '',
});

const rows = ref<EventCard[]>([]);
const cursor = ref<string | null>(null);
const hasMore = ref(true);
const loading = ref(false);
const hasLoadedOnce = ref(false);

const sentinel = ref<HTMLElement | null>(null);
let observer: IntersectionObserver | null = null;

const registerDialog = ref<InstanceType<typeof EventRegisterDialog> | null>(null);

async function loadMore() {
    if (loading.value || !hasMore.value) {
        return;
    }

    loading.value = true;

    const params = new URLSearchParams({ sort: 'asc' });

    if (cursor.value) {
        params.set('cursor', cursor.value);
    }

    if (form.status) {
        params.set('status', form.status);
    }

    if (form.from) {
        params.set('from', form.from);
    }

    if (form.to) {
        params.set('to', form.to);
    }

    if (form.city) {
        params.set('city', form.city);
    }

    try {
        const response = await fetch(`/events/data?${params.toString()}`, {
            headers: { Accept: 'application/json' },
        });
        const payload = await response.json();
        rows.value.push(...payload.data);
        cursor.value = payload.next_cursor;
        hasMore.value = payload.has_more;
        hasLoadedOnce.value = true;
    } finally {
        loading.value = false;
    }
}

function applyFilters() {
    rows.value = [];
    cursor.value = null;
    hasMore.value = true;
    hasLoadedOnce.value = false;
    loadMore();
}

function resetFilters() {
    form.status = '';
    form.from = today;
    form.to = '';
    form.city = '';
    applyFilters();
}

const statusVariant = (status: string) => {
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
};

function priceLabel(event: EventCard): string {
    const price = event.price.min ?? 0;

    if (!price) {
        return 'Free';
    }

    return `${event.price.currency} ${price.toFixed(0)}+`;
}

function openRegister(event: EventCard) {
    registerDialog.value?.show(event.id, event.name ?? 'this event');
}

onMounted(() => {
    observer = new IntersectionObserver(
        (entries) => {
            if (entries[0]?.isIntersecting) {
                loadMore();
            }
        },
        { rootMargin: '400px' },
    );

    if (sentinel.value) {
        observer.observe(sentinel.value);
    }

    loadMore();
});

onBeforeUnmount(() => observer?.disconnect());
</script>

<template>
    <Head title="Events — Discover" />

    <div class="mx-auto flex w-full max-w-7xl flex-col gap-6 p-6">
        <header class="flex flex-col gap-1">
            <h1 class="text-3xl font-bold tracking-tight">Discover events</h1>
            <p class="text-sm text-muted-foreground">
                {{
                    hasLoadedOnce
                        ? `${rows.length.toLocaleString()} events loaded${hasMore ? '+' : ''}`
                        : 'Browse upcoming events around the world'
                }}
            </p>
        </header>

        <!-- Filters -->
        <form
            class="grid grid-cols-1 gap-3 rounded-xl border bg-card p-4 sm:grid-cols-2 lg:grid-cols-5"
            @submit.prevent="applyFilters"
        >
            <div class="flex flex-col gap-1">
                <Label for="from" class="text-xs text-muted-foreground">From</Label>
                <input
                    id="from"
                    v-model="form.from"
                    type="date"
                    class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                />
            </div>
            <div class="flex flex-col gap-1">
                <Label for="to" class="text-xs text-muted-foreground">To</Label>
                <input
                    id="to"
                    v-model="form.to"
                    type="date"
                    class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                />
            </div>
            <div class="flex flex-col gap-1">
                <Label for="city" class="text-xs text-muted-foreground">Location</Label>
                <select
                    id="city"
                    v-model="form.city"
                    class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                >
                    <option value="">Anywhere</option>
                    <option v-for="c in cities" :key="c" :value="c">{{ c }}</option>
                </select>
            </div>
            <div class="flex flex-col gap-1">
                <Label for="status" class="text-xs text-muted-foreground">Status</Label>
                <select
                    id="status"
                    v-model="form.status"
                    class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                >
                    <option value="">All</option>
                    <option v-for="s in statuses" :key="s" :value="s">{{ s }}</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <Button type="submit" class="flex-1">Apply</Button>
                <Button type="button" variant="outline" @click="resetFilters">Reset</Button>
            </div>
        </form>

        <!-- Grid -->
        <TransitionGroup
            tag="div"
            class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3"
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="translate-y-3 opacity-0"
        >
            <article
                v-for="event in rows"
                :key="event.id"
                class="group flex flex-col overflow-hidden rounded-xl border bg-card shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-lg"
            >
                <div class="relative aspect-[16/10] overflow-hidden bg-muted">
                    <Link :href="`/events/${event.id}`" class="block h-full w-full">
                        <img
                            :src="event.images[0]"
                            :alt="event.name ?? 'Event'"
                            loading="lazy"
                            class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                        />
                    </Link>
                    <Badge
                        :variant="statusVariant(event.status)"
                        class="absolute left-3 top-3 capitalize shadow"
                    >
                        {{ event.status.replace('_', ' ') }}
                    </Badge>
                    <span
                        class="absolute right-3 top-3 rounded-full bg-background/90 px-2.5 py-1 text-xs font-medium shadow"
                    >
                        {{ priceLabel(event) }}
                    </span>
                </div>

                <div class="flex flex-1 flex-col gap-2 p-4">
                    <span class="text-xs font-medium uppercase tracking-wide text-primary">{{ event.type }}</span>
                    <Link
                        :href="`/events/${event.id}`"
                        class="line-clamp-1 text-lg font-semibold hover:underline"
                    >
                        {{ event.name ?? 'Untitled event' }}
                    </Link>
                    <p class="line-clamp-2 text-sm text-muted-foreground">
                        {{ event.description }}
                    </p>

                    <dl class="mt-auto space-y-1 pt-2 text-sm">
                        <div
                            v-if="eventDateTime(event.created_time)"
                            class="flex items-center gap-2"
                        >
                            <span aria-hidden="true">📅</span>
                            <time :datetime="eventDateTime(event.created_time)!.iso">
                                {{ eventDateTime(event.created_time)!.date }} ·
                                {{ eventDateTime(event.created_time)!.time }}
                                <span class="text-muted-foreground">{{ eventDateTime(event.created_time)!.tz }}</span>
                            </time>
                        </div>
                        <div class="flex items-center gap-2">
                            <span aria-hidden="true">📍</span>
                            <span>{{ event.address ?? 'Location TBA' }}</span>
                        </div>
                    </dl>

                    <Button
                        class="mt-3 w-full"
                        variant="secondary"
                        :disabled="event.status !== 'published'"
                        @click="openRegister(event)"
                    >
                        Register interest
                    </Button>
                </div>
            </article>
        </TransitionGroup>

        <div ref="sentinel" class="h-1"></div>

        <div class="py-4 text-center text-sm text-muted-foreground">
            <span v-if="loading">Loading…</span>
            <span v-else-if="hasLoadedOnce && rows.length === 0">No events match your filters.</span>
            <span v-else-if="!hasMore && rows.length > 0">You've reached the end.</span>
        </div>
    </div>

    <EventRegisterDialog ref="registerDialog" />
</template>
