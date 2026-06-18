<script setup lang="ts">
import { reactive, ref } from 'vue';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Toaster } from '@/components/ui/sonner';

const open = ref(false);
const target = ref<{ id: string; title: string } | null>(null);
const form = reactive({ name: '', email: '' });
const errors = ref<Record<string, string>>({});
const submitting = ref(false);

function show(id: string, title: string) {
    target.value = { id, title };
    form.name = '';
    form.email = '';
    errors.value = {};
    open.value = true;
}

defineExpose({ show });

function csrfToken(): string {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : '';
}

async function submit() {
    if (!target.value || submitting.value) {
        return;
    }

    submitting.value = true;
    errors.value = {};

    try {
        const response = await fetch(`/events/${target.value.id}/attendees`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-XSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify(form),
        });

        if (response.status === 422) {
            const data = await response.json();
            errors.value = Object.fromEntries(
                Object.entries(data.errors ?? {}).map(([key, value]) => [key, (value as string[])[0]]),
            );

            return;
        }

        const data = await response.json();
        toast[data.created ? 'success' : 'info'](data.message);
        open.value = false;
    } finally {
        submitting.value = false;
    }
}
</script>

<template>
    <Toaster position="top-center" rich-colors />

    <Dialog v-model:open="open">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Register for {{ target?.title ?? 'this event' }}</DialogTitle>
                <DialogDescription>We'll email you to confirm, then remind you before it starts.</DialogDescription>
            </DialogHeader>

            <form class="flex flex-col gap-4" @submit.prevent="submit">
                <div class="flex flex-col gap-1.5">
                    <Label for="reg-name">Name</Label>
                    <Input id="reg-name" v-model="form.name" type="text" autocomplete="name" />
                    <p v-if="errors.name" class="text-xs text-destructive">{{ errors.name }}</p>
                </div>
                <div class="flex flex-col gap-1.5">
                    <Label for="reg-email">Email</Label>
                    <Input id="reg-email" v-model="form.email" type="email" autocomplete="email" />
                    <p v-if="errors.email" class="text-xs text-destructive">{{ errors.email }}</p>
                </div>
                <DialogFooter>
                    <Button type="submit" :disabled="submitting">{{ submitting ? 'Submitting…' : 'Join the list' }}</Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
