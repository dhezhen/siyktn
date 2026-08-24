{{--
    Notifikasi mengambang untuk aksi Livewire.

    Dengarkan lewat: $this->dispatch('notify', type: 'success', message: '...')
    Tipe yang dikenal: success | error | warning | info
--}}
<div x-data="{
        antrean: [],
        tambah(detail) {
            const id = Date.now() + Math.random();
            this.antrean.push({ id, ...detail });
            setTimeout(() => this.buang(id), 5000);
        },
        buang(id) {
            this.antrean = this.antrean.filter(t => t.id !== id);
        },
     }"
     x-on:notify.window="tambah($event.detail)"
     class="pointer-events-none fixed bottom-5 right-5 z-[80] flex w-[min(24rem,calc(100vw-2.5rem))] flex-col gap-2">

    <template x-for="toast in antrean" :key="toast.id">
        <div x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-x-6 scale-95"
             x-transition:enter-end="opacity-100 translate-x-0 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 translate-x-6 scale-95"
             class="pointer-events-auto flex items-start gap-3 rounded-xl px-4 py-3 text-sm text-white shadow-lg ring-1 ring-black/5"
             :class="{
                'bg-emerald-600': toast.type === 'success',
                'bg-rose-600': toast.type === 'error',
                'bg-amber-600': toast.type === 'warning',
                'bg-slate-800': toast.type === 'info' || ! toast.type,
             }">
            <svg class="mt-0.5 size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <template x-if="toast.type === 'success'">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </template>
                <template x-if="toast.type === 'error'">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </template>
                <template x-if="toast.type !== 'success' && toast.type !== 'error'">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                </template>
            </svg>

            <p class="flex-1 leading-relaxed" x-text="toast.message"></p>

            <button type="button" @click="buang(toast.id)"
                    class="shrink-0 rounded p-0.5 opacity-60 transition hover:opacity-100"
                    aria-label="Tutup notifikasi">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </template>
</div>
