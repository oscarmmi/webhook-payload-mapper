<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

type Sample = {
    id: number;
    label: string;
    payload: Record<string, unknown>;
};

type MappingResult = {
    id: number;
    payload: {
        event_type: string | null;
        order_id: string | null;
        customer_email: string | null;
    };
    valid: boolean;
    errors: string[];
    unknown_keys: string[];
    status: string;
};

const props = defineProps<{
    samples: Sample[];
    llmMode: string;
}>();

const fallbackPayload = {
    event_type: 'order.paid',
    orderId: 'HD-2048',
    email: 'alex.rivera@example.test',
    payment: { method: 'card', currency: 'USD' },
};
const emptyOutput = JSON.stringify(
    { event_type: null, order_id: null, customer_email: null },
    null,
    2,
);

const editor = ref(
    JSON.stringify(props.samples[0]?.payload ?? fallbackPayload, null, 2),
);
const result = ref<MappingResult | null>(null);
const busy = ref(false);
const requestError = ref('');
const copied = ref(false);

function selectSample(sample: Sample): void {
    editor.value = JSON.stringify(sample.payload, null, 2);
    result.value = null;
    requestError.value = '';
}

async function mapPayload(): Promise<void> {
    requestError.value = '';
    result.value = null;

    let parsedPayload: unknown;

    try {
        parsedPayload = JSON.parse(editor.value);
    } catch {
        requestError.value =
            'This is not valid JSON. Check the brackets, commas, and quotes.';
        return;
    }

    if (
        parsedPayload === null ||
        typeof parsedPayload !== 'object' ||
        Array.isArray(parsedPayload)
    ) {
        requestError.value = 'A webhook payload must be a JSON object.';
        return;
    }

    busy.value = true;

    try {
        const response = await fetch('/webhooks/map', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ payload_json: parsedPayload }),
        });
        const body = (await response.json()) as MappingResult & {
            message?: string;
        };

        if (!response.ok) {
            requestError.value =
                body.message ?? 'The payload could not be processed.';
            return;
        }

        result.value = body;
    } catch {
        requestError.value =
            'The mapper could not be reached. Please try again.';
    } finally {
        busy.value = false;
    }
}

async function copyResult(): Promise<void> {
    if (!result.value) return;

    try {
        await navigator.clipboard.writeText(
            JSON.stringify(result.value.payload, null, 2),
        );
        copied.value = true;
        window.setTimeout(() => (copied.value = false), 1600);
    } catch {
        copied.value = false;
    }
}
</script>

<template>
    <Head title="Webhook Payload Mapper" />

    <div class="min-h-screen bg-[#f4f6f8] text-[#172126]">
        <header class="border-b border-[#dfe5e7] bg-white">
            <div
                class="mx-auto flex max-w-[1440px] items-center justify-between px-5 py-4 sm:px-8"
            >
                <a
                    href="/"
                    class="flex items-center gap-3"
                    aria-label="Webhook Payload Mapper home"
                >
                    <span
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#153b35] text-white"
                    >
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            class="h-5 w-5"
                            aria-hidden="true"
                        >
                            <path
                                d="M7 7h10M7 12h6M7 17h10M4 4h16v16H4z"
                                stroke="currentColor"
                                stroke-width="1.7"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                    </span>
                    <span>
                        <span class="block text-sm font-semibold tracking-tight"
                            >Harbor Desk</span
                        >
                        <span
                            class="block text-[11px] font-medium tracking-wide text-[#7b888c]"
                            >WEBHOOK WORKSPACE</span
                        >
                    </span>
                </a>

                <div
                    class="hidden items-center gap-2 rounded-full border border-[#dfe8e3] bg-[#f4f8f5] px-3 py-1.5 text-xs font-medium text-[#426452] sm:flex"
                >
                    <span class="h-2 w-2 rounded-full bg-[#4a9a70]"></span>
                    {{ llmMode }}
                </div>
                <a
                    class="text-sm font-medium text-[#647276] transition hover:text-[#153b35]"
                    href="https://github.com/oscarmmi/webhook-payload-mapper"
                    target="_blank"
                    rel="noreferrer"
                >
                    View project <span aria-hidden="true">↗</span>
                </a>
            </div>
        </header>

        <main class="mx-auto max-w-[1440px] px-5 py-9 sm:px-8 sm:py-12">
            <div
                class="mb-8 flex flex-col justify-between gap-5 md:flex-row md:items-end"
            >
                <div class="max-w-2xl">
                    <div
                        class="mb-3 flex items-center gap-2 text-xs font-semibold tracking-[0.16em] text-[#43806d] uppercase"
                    >
                        <span class="h-px w-6 bg-[#43806d]"></span>
                        Inbound event toolkit
                    </div>
                    <h1
                        class="text-3xl font-semibold tracking-[-0.04em] text-[#172126] sm:text-[40px]"
                    >
                        Make every webhook speak the same language.
                    </h1>
                    <p
                        class="mt-3 max-w-xl text-[15px] leading-6 text-[#69777b]"
                    >
                        Normalize inconsistent payloads into Harbor Desk’s event
                        contract. Known aliases are mapped deterministically; AI
                        only gets a look at fields the rules don’t recognize.
                    </p>
                </div>
                <div
                    class="flex items-center gap-3 rounded-xl border border-[#e0e7e9] bg-white px-4 py-3 shadow-sm"
                >
                    <div
                        class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#eef4f1] text-[#376b57]"
                    >
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            class="h-5 w-5"
                            aria-hidden="true"
                        >
                            <path
                                d="M12 3v3m0 12v3M3 12h3m12 0h3M5.64 5.64l2.12 2.12m8.48 8.48 2.12 2.12m0-12.72-2.12 2.12m-8.48 8.48-2.12 2.12"
                                stroke="currentColor"
                                stroke-width="1.7"
                                stroke-linecap="round"
                            />
                            <circle
                                cx="12"
                                cy="12"
                                r="4"
                                stroke="currentColor"
                                stroke-width="1.7"
                            />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-[#758287]">
                            Target contract
                        </p>
                        <p class="text-sm font-semibold text-[#24343a]">
                            Harbor Desk · v1.0
                        </p>
                    </div>
                    <span
                        class="ml-3 rounded-md bg-[#f1f3f4] px-2 py-1 font-mono text-[10px] text-[#758287]"
                        >JSON</span
                    >
                </div>
            </div>

            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-2">
                    <span
                        class="mr-1 text-xs font-semibold tracking-[0.12em] text-[#8a9699] uppercase"
                        >Try a sample</span
                    >
                    <button
                        v-for="sample in samples"
                        :key="sample.id"
                        type="button"
                        class="rounded-full border border-[#dce4e6] bg-white px-3 py-1.5 text-xs font-medium text-[#526267] transition hover:border-[#9bb9ab] hover:text-[#285844]"
                        @click="selectSample(sample)"
                    >
                        {{ sample.label }}
                    </button>
                </div>
                <p class="text-xs text-[#8a9699]">
                    Fictional data · safe to experiment
                </p>
            </div>

            <div class="grid gap-5 xl:grid-cols-[1.03fr_0.97fr]">
                <section
                    class="overflow-hidden rounded-2xl border border-[#dfe6e8] bg-white shadow-[0_8px_28px_rgba(23,33,38,0.035)]"
                    aria-labelledby="source-heading"
                >
                    <div
                        class="flex items-center justify-between border-b border-[#e8edef] px-5 py-4 sm:px-6"
                    >
                        <div class="flex items-center gap-3">
                            <span
                                class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#eff2f3] font-mono text-[11px] font-bold text-[#5d6d72]"
                                >01</span
                            >
                            <div>
                                <h2
                                    id="source-heading"
                                    class="text-sm font-semibold"
                                >
                                    Source payload
                                </h2>
                                <p class="mt-0.5 text-xs text-[#8a9699]">
                                    Paste a JSON webhook body
                                </p>
                            </div>
                        </div>
                        <span
                            class="rounded-md border border-[#e4e9ea] px-2 py-1 font-mono text-[10px] text-[#849195]"
                            >application/json</span
                        >
                    </div>

                    <div class="p-4 sm:p-5">
                        <div
                            class="overflow-hidden rounded-xl border border-[#263b3b] bg-[#142322]"
                        >
                            <div
                                class="flex h-10 items-center justify-between border-b border-white/10 px-4"
                            >
                                <div
                                    class="flex items-center gap-1.5"
                                    aria-hidden="true"
                                >
                                    <span
                                        class="h-2 w-2 rounded-full bg-[#ea887b]"
                                    ></span>
                                    <span
                                        class="h-2 w-2 rounded-full bg-[#e6be6e]"
                                    ></span>
                                    <span
                                        class="h-2 w-2 rounded-full bg-[#6fb28e]"
                                    ></span>
                                </div>
                                <span
                                    class="font-mono text-[10px] text-[#77918a]"
                                    >incoming-event.json</span
                                >
                            </div>
                            <textarea
                                v-model="editor"
                                spellcheck="false"
                                aria-label="Webhook JSON payload"
                                class="min-h-[330px] w-full resize-y bg-transparent px-5 py-4 font-mono text-[13px] leading-[1.8] text-[#d5e6d9] outline-none placeholder:text-[#607871] focus:ring-2 focus:ring-[#73b28e]/50 focus:ring-inset"
                                placeholder="Paste your webhook JSON here..."
                                @keydown.ctrl.enter.prevent="mapPayload"
                            ></textarea>
                        </div>
                        <div
                            v-if="requestError"
                            role="alert"
                            class="mt-3 rounded-lg border border-[#f1d2cc] bg-[#fff5f2] px-3.5 py-2.5 text-sm text-[#a4493b]"
                        >
                            {{ requestError }}
                        </div>
                        <div
                            class="mt-4 flex flex-col-reverse justify-between gap-3 sm:flex-row sm:items-center"
                        >
                            <p class="text-xs text-[#879397]">
                                <kbd
                                    class="rounded border border-[#dfe6e8] bg-[#f7f9f9] px-1.5 py-0.5 font-mono text-[10px]"
                                    >Ctrl</kbd
                                >
                                +
                                <kbd
                                    class="rounded border border-[#dfe6e8] bg-[#f7f9f9] px-1.5 py-0.5 font-mono text-[10px]"
                                    >Enter</kbd
                                >
                                to map
                            </p>
                            <button
                                type="button"
                                class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#1f5143] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#194336] focus:ring-2 focus:ring-[#79aa91] focus:ring-offset-2 focus:outline-none disabled:cursor-wait disabled:opacity-70"
                                :disabled="busy"
                                @click="mapPayload"
                            >
                                <svg
                                    v-if="busy"
                                    class="h-4 w-4 animate-spin"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    aria-hidden="true"
                                >
                                    <circle
                                        cx="12"
                                        cy="12"
                                        r="9"
                                        stroke="currentColor"
                                        stroke-opacity=".25"
                                        stroke-width="3"
                                    />
                                    <path
                                        d="M21 12a9 9 0 0 0-9-9"
                                        stroke="currentColor"
                                        stroke-width="3"
                                        stroke-linecap="round"
                                    />
                                </svg>
                                <svg
                                    v-else
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    class="h-4 w-4"
                                    aria-hidden="true"
                                >
                                    <path
                                        d="M4 12h15m0 0-6-6m6 6-6 6"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                </svg>
                                {{ busy ? 'Mapping payload…' : 'Map payload' }}
                            </button>
                        </div>
                    </div>
                </section>

                <section
                    class="overflow-hidden rounded-2xl border border-[#dfe6e8] bg-white shadow-[0_8px_28px_rgba(23,33,38,0.035)]"
                    aria-labelledby="result-heading"
                >
                    <div
                        class="flex items-center justify-between border-b border-[#e8edef] px-5 py-4 sm:px-6"
                    >
                        <div class="flex items-center gap-3">
                            <span
                                class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#edf4f0] font-mono text-[11px] font-bold text-[#43806d]"
                                >02</span
                            >
                            <div>
                                <h2
                                    id="result-heading"
                                    class="text-sm font-semibold"
                                >
                                    Mapped event
                                </h2>
                                <p class="mt-0.5 text-xs text-[#8a9699]">
                                    Strict Harbor Desk schema
                                </p>
                            </div>
                        </div>
                        <span
                            v-if="result"
                            :class="
                                result.valid
                                    ? 'border-[#cfe5d5] bg-[#f0f8f2] text-[#36704d]'
                                    : 'border-[#f0d7c3] bg-[#fff7ef] text-[#a86731]'
                            "
                            class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[11px] font-semibold"
                        >
                            <span
                                :class="
                                    result.valid
                                        ? 'bg-[#4c9a69]'
                                        : 'bg-[#d99548]'
                                "
                                class="h-1.5 w-1.5 rounded-full"
                            ></span>
                            {{
                                result.valid ? 'Valid event' : 'Needs attention'
                            }}
                        </span>
                        <span
                            v-else
                            class="rounded-md bg-[#f3f5f5] px-2 py-1 font-mono text-[10px] text-[#849195]"
                            >AWAITING INPUT</span
                        >
                    </div>

                    <div class="space-y-4 p-4 sm:p-5">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div
                                class="rounded-xl border border-[#e4e9ea] bg-[#fbfcfc] p-4"
                            >
                                <div
                                    class="mb-3 flex items-center justify-between"
                                >
                                    <span
                                        class="text-[11px] font-semibold tracking-[0.11em] text-[#849195] uppercase"
                                        >event_type</span
                                    >
                                    <span
                                        class="font-mono text-[10px] text-[#a6afb1]"
                                        >string · required</span
                                    >
                                </div>
                                <p
                                    class="min-h-6 font-mono text-[13px] break-all"
                                    :class="
                                        result?.payload.event_type
                                            ? 'text-[#253c35]'
                                            : 'text-[#b1b9bb]'
                                    "
                                >
                                    {{ result?.payload.event_type ?? '—' }}
                                </p>
                            </div>
                            <div
                                class="rounded-xl border border-[#e4e9ea] bg-[#fbfcfc] p-4"
                            >
                                <div
                                    class="mb-3 flex items-center justify-between"
                                >
                                    <span
                                        class="text-[11px] font-semibold tracking-[0.11em] text-[#849195] uppercase"
                                        >order_id</span
                                    >
                                    <span
                                        class="font-mono text-[10px] text-[#a6afb1]"
                                        >string · optional</span
                                    >
                                </div>
                                <p
                                    class="min-h-6 font-mono text-[13px] break-all"
                                    :class="
                                        result?.payload.order_id
                                            ? 'text-[#253c35]'
                                            : 'text-[#b1b9bb]'
                                    "
                                >
                                    {{ result?.payload.order_id ?? '—' }}
                                </p>
                            </div>
                            <div
                                class="rounded-xl border border-[#e4e9ea] bg-[#fbfcfc] p-4 sm:col-span-2"
                            >
                                <div
                                    class="mb-3 flex items-center justify-between"
                                >
                                    <span
                                        class="text-[11px] font-semibold tracking-[0.11em] text-[#849195] uppercase"
                                        >customer_email</span
                                    >
                                    <span
                                        class="font-mono text-[10px] text-[#a6afb1]"
                                        >email · required</span
                                    >
                                </div>
                                <p
                                    class="min-h-6 font-mono text-[13px] break-all"
                                    :class="
                                        result?.payload.customer_email
                                            ? 'text-[#253c35]'
                                            : 'text-[#b1b9bb]'
                                    "
                                >
                                    {{ result?.payload.customer_email ?? '—' }}
                                </p>
                            </div>
                        </div>

                        <div
                            v-if="result?.errors.length"
                            class="rounded-xl border border-[#f0d9d3] bg-[#fff8f6] p-4"
                            role="alert"
                        >
                            <div
                                class="mb-2 flex items-center gap-2 text-xs font-semibold text-[#a4493b]"
                            >
                                <svg
                                    viewBox="0 0 20 20"
                                    fill="none"
                                    class="h-4 w-4"
                                    aria-hidden="true"
                                >
                                    <path
                                        d="M10 6v4m0 3h.01M8.27 3.88 1.9 15a1.4 1.4 0 0 0 1.21 2.1h13.78A1.4 1.4 0 0 0 18.1 15L11.73 3.88a2 2 0 0 0-3.46 0Z"
                                        stroke="currentColor"
                                        stroke-width="1.5"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                </svg>
                                Validation errors
                            </div>
                            <ul
                                class="space-y-1 text-xs leading-5 text-[#9e5a4f]"
                            >
                                <li v-for="error in result.errors" :key="error">
                                    {{ error }}
                                </li>
                            </ul>
                        </div>

                        <div
                            class="rounded-xl border border-[#e7e9dc] bg-[#fbfbf6] p-4"
                        >
                            <div
                                class="mb-2 flex items-center gap-2 text-xs font-semibold text-[#74774d]"
                            >
                                <svg
                                    viewBox="0 0 20 20"
                                    fill="none"
                                    class="h-4 w-4"
                                    aria-hidden="true"
                                >
                                    <path
                                        d="M10 2.5v2m0 11v2m7.5-7.5h-2m-11 0h-2m12.8-5.3-1.4 1.4m-7.8 7.8-1.4 1.4m10.6 0-1.4-1.4m-7.8-7.8L5.7 4.7"
                                        stroke="currentColor"
                                        stroke-width="1.4"
                                        stroke-linecap="round"
                                    />
                                    <circle
                                        cx="10"
                                        cy="10"
                                        r="3.2"
                                        stroke="currentColor"
                                        stroke-width="1.4"
                                    />
                                </svg>
                                Unknown-key notes
                            </div>
                            <p
                                v-if="result?.unknown_keys.length"
                                class="text-xs leading-5 text-[#76795c]"
                            >
                                Unrecognized source keys:
                                <span
                                    v-for="(key, index) in result.unknown_keys"
                                    :key="key"
                                    class="font-mono font-medium"
                                    >{{ index ? ', ' : '' }}{{ key }}</span
                                >. Unknown keys are checked only for missing
                                schema fields; unmapped values are never added
                                to the event contract.
                            </p>
                            <p
                                v-else-if="result"
                                class="text-xs leading-5 text-[#92947c]"
                            >
                                No unknown keys. All supplied fields matched
                                deterministic aliases.
                            </p>
                            <p v-else class="text-xs leading-5 text-[#92947c]">
                                Unknown fields will be listed here after
                                mapping.
                            </p>
                        </div>

                        <div
                            class="overflow-hidden rounded-xl border border-[#263b3b] bg-[#142322]"
                        >
                            <div
                                class="flex items-center justify-between border-b border-white/10 px-4 py-2.5"
                            >
                                <span
                                    class="font-mono text-[10px] text-[#91a69f]"
                                    >harbor-desk-event.json</span
                                >
                                <button
                                    v-if="result"
                                    type="button"
                                    class="text-[10px] font-semibold text-[#a7c5b3] transition hover:text-white"
                                    @click="copyResult"
                                >
                                    {{ copied ? 'Copied' : 'Copy JSON' }}
                                </button>
                                <span v-else class="text-[10px] text-[#607871]"
                                    >Output preview</span
                                >
                            </div>
                            <pre
                                class="min-h-[120px] overflow-x-auto px-4 py-3 font-mono text-xs leading-6 text-[#d5e6d9]"
                                >{{
                                    result
                                        ? JSON.stringify(
                                              result.payload,
                                              null,
                                              2,
                                          )
                                        : emptyOutput
                                }}</pre>
                        </div>
                    </div>
                </section>
            </div>

            <footer
                class="mt-7 flex flex-col gap-2 border-t border-[#dfe6e8] pt-5 text-xs text-[#899599] sm:flex-row sm:items-center sm:justify-between"
            >
                <p>
                    Alias rules first. Optional AI for unknown keys. Validation
                    always wins.
                </p>
                <p>
                    Harbor Desk schema ·
                    <span class="font-mono"
                        >event_type · order_id · customer_email</span
                    >
                </p>
            </footer>
        </main>
    </div>
</template>
