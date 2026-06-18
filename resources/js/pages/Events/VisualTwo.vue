<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, onMounted, reactive, ref } from 'vue';
import EventRegisterDialog from '@/components/EventRegisterDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { eventDateTime } from '@/lib/datetime';

interface EventRow {
    id: string;
    name: string | null;
    type: string;
    status: string;
    created_time: number | null;
    images: string[];
    address: string | null;
}

interface DayGroup {
    key: string;
    weekday: string;
    dayNum: string;
    events: EventRow[];
}

const props = defineProps<{
    filters: { status: string | null; city: string | null };
    statuses: string[];
    cities: string[];
}>();

const form = reactive({
    status: props.filters.status ?? '',
    city: props.filters.city ?? '',
});

const cursor = ref(new Date(new Date().getFullYear(), new Date().getMonth(), 1));
const events = ref<EventRow[]>([]);
const loading = ref(false);
const registerDialog = ref<InstanceType<typeof EventRegisterDialog> | null>(null);

const pad = (n: number) => String(n).padStart(2, '0');
const isoDay = (d: Date) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;

const monthLabel = computed(() =>
    new Intl.DateTimeFormat(undefined, { month: 'long', year: 'numeric' }).format(cursor.value),
);

const groups = computed<DayGroup[]>(() => {
    const map = new Map<string, EventRow[]>();

    for (const event of events.value) {
        if (!event.created_time) {
            continue;
        }

        const d = new Date(event.created_time * 1000);
        const key = isoDay(d);

        if (!map.has(key)) {
            map.set(key, []);
        }

        map.get(key)!.push(event);
    }

    return Array.from(map.entries())
        .sort(([a], [b]) => a.localeCompare(b))
        .map(([key, list]) => {
            const d = new Date(`${key}T00:00:00`);

            return {
                key,
                weekday: new Intl.DateTimeFormat(undefined, { weekday: 'short' }).format(d),
                dayNum: String(d.getDate()),
                events: list.sort((x, y) => (x.created_time ?? 0) - (y.created_time ?? 0)),
            };
        });
});

async function loadMonth() {
    loading.value = true;
    events.value = [];

    const first = new Date(cursor.value.getFullYear(), cursor.value.getMonth(), 1);
    const last = new Date(cursor.value.getFullYear(), cursor.value.getMonth() + 1, 0);
    const from = isoDay(first);
    const to = isoDay(last);

    let pageCursor: string | null = null;
    let more = true;
    let guard = 0;

    try {
        while (more && guard < 50) {
            const params = new URLSearchParams({ sort: 'asc', from, to });

            if (pageCursor) {
                params.set('cursor', pageCursor);
            }

            if (form.status) {
                params.set('status', form.status);
            }

            if (form.city) {
                params.set('city', form.city);
            }

            const response = await fetch(`/events/data?${params.toString()}`, {
                headers: { Accept: 'application/json' },
            });
            const payload = await response.json();
            events.value.push(...payload.data);
            pageCursor = payload.next_cursor;
            more = payload.has_more;
            guard++;
        }
    } finally {
        loading.value = false;
    }
}

function shiftMonth(delta: number) {
    cursor.value = new Date(cursor.value.getFullYear(), cursor.value.getMonth() + delta, 1);
    loadMonth();
}

function goToday() {
    cursor.value = new Date(new Date().getFullYear(), new Date().getMonth(), 1);
    loadMonth();
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

function openRegister(event: EventRow) {
    registerDialog.value?.show(event.id, event.name ?? 'this event');
}

onMounted(loadMonth);
</script>

<template>
    <Head title="Events — Agenda" />

    <div class="mx-auto flex w-full max-w-5xl flex-col gap-6 p-6">
        <header class="flex flex-col gap-4">
            <div>
                <h1 class="text-3xl font-bold tracking-tight">Events agenda</h1>
                <p class="text-sm text-muted-foreground">Browse what's on, day by day.</p>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <Button variant="outline" size="icon" aria-label="Previous month" @click="shiftMonth(-1)">‹</Button>
                    <span class="min-w-44 text-center text-lg font-semibold">{{ monthLabel }}</span>
                    <Button variant="outline" size="icon" aria-label="Next month" @click="shiftMonth(1)">›</Button>
                    <Button variant="ghost" size="sm" @click="goToday">Today</Button>
                </div>

                <div class="flex items-end gap-3">
                    <div class="flex flex-col gap-1">
                        <Label for="city" class="text-xs text-muted-foreground">Location</Label>
                        <select
                            id="city"
                            v-model="form.city"
                            class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                            @change="loadMonth"
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
                            @change="loadMonth"
                        >
                            <option value="">All</option>
                            <option v-for="s in statuses" :key="s" :value="s">{{ s }}</option>
                        </select>
                    </div>
                </div>
            </div>
        </header>

        <p v-if="loading" class="py-10 text-center text-sm text-muted-foreground">Loading {{ monthLabel }}…</p>
        <p v-else-if="groups.length === 0" class="py-10 text-center text-sm text-muted-foreground">
            No events in {{ monthLabel }}.
        </p>

        <TransitionGroup v-else name="day" tag="div" class="flex flex-col gap-6">
            <section v-for="group in groups" :key="group.key" class="flex gap-4">
                <div class="flex w-14 flex-col items-center pt-1">
                    <span class="text-xs uppercase text-muted-foreground">{{ group.weekday }}</span>
                    <span class="text-2xl font-bold">{{ group.dayNum }}</span>
                </div>

                <div class="flex flex-1 flex-col gap-3 border-l pl-4">
                    <article
                        v-for="event in group.events"
                        :key="event.id"
                        class="flex gap-4 rounded-lg border bg-card p-3 transition hover:shadow-md"
                    >
                        <img
                            :src="event.images[0]"
                            :alt="event.name ?? 'Event'"
                            loading="lazy"
                            class="hidden h-16 w-24 shrink-0 rounded-md object-cover sm:block"
                        />
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 text-xs text-muted-foreground">
                                <span class="font-medium text-foreground">
                                    {{ eventDateTime(event.created_time)?.time }}
                                    {{ eventDateTime(event.created_time)?.tz }}
                                </span>
                                <span>·</span>
                                <span class="uppercase tracking-wide text-primary">{{ event.type }}</span>
                            </div>
                            <h3 class="truncate font-semibold">{{ event.name ?? 'Untitled event' }}</h3>
                            <p class="truncate text-sm text-muted-foreground">📍 {{ event.address ?? 'Location TBA' }}</p>
                        </div>
                        <div class="flex shrink-0 flex-col items-end justify-between gap-2">
                            <Badge :variant="statusVariant(event.status)" class="capitalize">
                                {{ event.status.replace('_', ' ') }}
                            </Badge>
                            <Button size="sm" variant="secondary" @click="openRegister(event)">Register</Button>
                        </div>
                    </article>
                </div>
            </section>
        </TransitionGroup>
    </div>

    <EventRegisterDialog ref="registerDialog" />
</template>

<style scoped>
.day-enter-active {
    transition: all 0.35s ease;
}
.day-enter-from {
    opacity: 0;
    transform: translateY(10px);
}
</style>
