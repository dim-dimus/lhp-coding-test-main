<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import EventRegisterDialog from '@/components/EventRegisterDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { eventDateTime } from '@/lib/datetime';
import { priceLabel, statusVariant } from '@/lib/events';

interface EventDetail {
    id: string;
    type: string;
    status: string;
    created_time: number | null;
    address: string | null;
    images: string[];
    payload: {
        name?: string;
        description?: string;
        venue?: { name?: string; capacity?: number | string };
        pricing?: { currency?: string; min_price?: number | string };
    };
}

const props = defineProps<{ event: EventDetail }>();

const registerDialog = ref<InstanceType<typeof EventRegisterDialog> | null>(null);

const when = eventDateTime(props.event.created_time);

function goBack() {
    // Return to wherever the user came from; fall back to the grid on a direct hit.
    if (window.history.length > 1) {
        window.history.back();
    } else {
        router.visit('/events-visual-1');
    }
}

const eventPrice = priceLabel(
    Number(props.event.payload.pricing?.min_price ?? 0),
    props.event.payload.pricing?.currency ?? 'USD',
);

function openRegister() {
    registerDialog.value?.show(props.event.id, props.event.payload.name ?? 'this event');
}
</script>

<template>
    <Head :title="event.payload.name ?? 'Event'" />

    <div class="mx-auto flex w-full max-w-4xl flex-col gap-6 p-6">
        <button type="button" class="self-start text-sm text-primary hover:underline" @click="goBack">
            ← Back
        </button>

        <!-- Gallery (every event ships 2+ local images) -->
        <div class="grid grid-cols-4 gap-2">
            <img
                :src="event.images[0]"
                :alt="event.payload.name ?? 'Event'"
                class="col-span-4 aspect-[21/9] w-full rounded-xl object-cover"
            />
            <img
                v-for="(img, i) in event.images.slice(1)"
                :key="i"
                :src="img"
                :alt="`${event.payload.name ?? 'Event'} ${i + 2}`"
                class="col-span-2 aspect-video w-full rounded-lg object-cover sm:col-span-1"
            />
        </div>

        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex flex-col gap-2">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-medium uppercase tracking-wide text-primary">{{ event.type }}</span>
                    <Badge :variant="statusVariant(event.status)" class="capitalize">
                        {{ event.status.replace('_', ' ') }}
                    </Badge>
                </div>
                <h1 class="text-3xl font-bold tracking-tight">{{ event.payload.name ?? 'Untitled event' }}</h1>
            </div>
            <Button :disabled="event.status !== 'published'" @click="openRegister">Register interest</Button>
        </div>

        <p v-if="event.payload.description" class="text-muted-foreground">
            {{ event.payload.description }}
        </p>

        <dl class="grid grid-cols-1 gap-4 rounded-xl border bg-card p-4 sm:grid-cols-2">
            <div v-if="when">
                <dt class="text-xs uppercase text-muted-foreground">When</dt>
                <dd class="font-medium">
                    {{ when.date }} · {{ when.time }} <span class="text-muted-foreground">{{ when.tz }}</span>
                </dd>
            </div>
            <div>
                <dt class="text-xs uppercase text-muted-foreground">Where</dt>
                <dd class="font-medium">{{ event.address ?? 'Location to be announced' }}</dd>
            </div>
            <div v-if="event.payload.venue?.name">
                <dt class="text-xs uppercase text-muted-foreground">Venue</dt>
                <dd class="font-medium">{{ event.payload.venue.name }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase text-muted-foreground">Price</dt>
                <dd class="font-medium">{{ eventPrice }}</dd>
            </div>
        </dl>
    </div>

    <EventRegisterDialog ref="registerDialog" />
</template>
