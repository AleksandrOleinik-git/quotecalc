@extends('layouts.app')

@section('title', 'Price calculator')

@php
    $initial = [
        'productId' => old('product_id'),
        'materialId' => old('material_id'),
        'width' => old('width_mm'),
        'height' => old('height_mm'),
        'quantity' => old('quantity'),
        'optionIds' => array_map('intval', (array) old('option_ids', [])),
        'includeVat' => old('include_vat', '1') === '1',
    ];
@endphp

@section('content')
<div x-data="calculator(@js($products), @js($initial))" x-cloak class="grid gap-8 lg:grid-cols-5">
    @if ($products === [])
        <p class="lg:col-span-5 rounded-lg bg-amber-50 p-4 text-amber-800">No products are available yet. Run <code>php artisan db:seed</code>.</p>
    @endif

    {{-- Configuration --}}
    <section class="space-y-6 lg:col-span-3">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Get an instant price</h1>
            <p class="mt-1 text-slate-500">Choose a product, size and finish. The price updates as you go.</p>
        </div>

        <div class="grid gap-3 sm:grid-cols-2">
            <template x-for="p in products" :key="p.id">
                <button type="button" @click="selectProduct(p.id)"
                        :class="p.id === productId ? 'border-indigo-500 ring-2 ring-indigo-200 bg-white' : 'border-slate-200 bg-white hover:border-slate-300'"
                        class="rounded-xl border p-4 text-left transition">
                    <div class="font-medium" x-text="p.name"></div>
                    <div class="mt-1 text-sm text-slate-500 line-clamp-2" x-text="p.description"></div>
                </button>
            </template>
        </div>

        <template x-if="product">
            <div class="space-y-6 rounded-xl border border-slate-200 bg-white p-6">
                <div class="grid gap-4 sm:grid-cols-3">
                    <label class="block">
                        <span class="text-sm font-medium">Width, mm</span>
                        <input type="number" x-model.number="width" :min="product.size_limits_mm.min_width" :max="product.size_limits_mm.max_width"
                               class="mt-1 w-full rounded-lg border-slate-300 px-3 py-2 ring-1 ring-slate-200 focus:ring-indigo-400">
                        <span class="text-xs text-slate-400" x-text="`${product.size_limits_mm.min_width}–${product.size_limits_mm.max_width} mm`"></span>
                    </label>
                    <label class="block">
                        <span class="text-sm font-medium">Height, mm</span>
                        <input type="number" x-model.number="height" :min="product.size_limits_mm.min_height" :max="product.size_limits_mm.max_height"
                               class="mt-1 w-full rounded-lg border-slate-300 px-3 py-2 ring-1 ring-slate-200 focus:ring-indigo-400">
                        <span class="text-xs text-slate-400" x-text="`${product.size_limits_mm.min_height}–${product.size_limits_mm.max_height} mm`"></span>
                    </label>
                    <label class="block">
                        <span class="text-sm font-medium">Quantity</span>
                        <input type="number" min="1" x-model.number="quantity"
                               class="mt-1 w-full rounded-lg border-slate-300 px-3 py-2 ring-1 ring-slate-200 focus:ring-indigo-400">
                        <span class="text-xs text-slate-400" x-show="product.quantity_tiers.length"
                              x-text="'Discounts from ' + product.quantity_tiers.map(t => `${t.min_quantity}+ (−${t.discount_bps / 100}%)`).join(', ')"></span>
                    </label>
                </div>

                <fieldset>
                    <legend class="text-sm font-medium">Material</legend>
                    <div class="mt-2 grid gap-2 sm:grid-cols-2">
                        <template x-for="m in product.materials" :key="m.id">
                            <label class="flex cursor-pointer items-center gap-3 rounded-lg border p-3"
                                   :class="m.id === materialId ? 'border-indigo-500 bg-indigo-50' : 'border-slate-200'">
                                <input type="radio" name="material" :value="m.id" x-model.number="materialId" class="text-indigo-600">
                                <span class="flex-1" x-text="m.name"></span>
                                <span class="text-sm text-slate-500" x-text="money(m.price_cents) + (m.pricing_unit === 'per_m2' ? '/m²' : '/pc')"></span>
                            </label>
                        </template>
                    </div>
                </fieldset>

                <fieldset x-show="product.options.length">
                    <legend class="text-sm font-medium">Options</legend>
                    <div class="mt-2 grid gap-2 sm:grid-cols-2">
                        <template x-for="o in product.options" :key="o.id">
                            <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-slate-200 p-3">
                                <input type="checkbox" :value="o.id" x-model.number="optionIds" class="rounded text-indigo-600">
                                <span class="flex-1" x-text="o.name"></span>
                                <span class="text-sm text-slate-500" x-text="optionHint(o)"></span>
                            </label>
                        </template>
                    </div>
                </fieldset>

                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" x-model="includeVat" class="rounded text-indigo-600">
                    Show prices including VAT ({{ rtrim(rtrim(number_format($vatRateBps / 100, 2), '0'), '.') }}%)
                </label>
            </div>
        </template>
    </section>

    {{-- Price + request form --}}
    <aside class="space-y-6 lg:col-span-2">
        <div class="sticky top-6 space-y-6">
            <div class="rounded-xl border border-slate-200 bg-white p-6" :class="loading && 'opacity-60'">
                <h2 class="text-sm font-medium uppercase tracking-wide text-slate-500">Your price</h2>

                <template x-if="Object.keys(errors).length">
                    <ul class="mt-3 space-y-1 rounded-lg bg-rose-50 p-3 text-sm text-rose-700">
                        <template x-for="(messages, field) in errors" :key="field">
                            <li x-text="messages[0]"></li>
                        </template>
                    </ul>
                </template>

                <template x-if="result && !Object.keys(errors).length">
                    <div>
                        <div class="mt-2 text-4xl font-semibold tracking-tight" x-text="result.formatted.total"></div>
                        <div class="text-sm text-slate-500">
                            <span x-text="result.include_vat ? 'incl. VAT' : 'excl. VAT'"></span> ·
                            <span x-text="result.formatted.unit_net + ' net per piece'"></span>
                        </div>

                        <table class="mt-5 w-full text-sm">
                            <tbody class="divide-y divide-slate-100">
                                <template x-for="(line, i) in result.lines" :key="i">
                                    <tr>
                                        <td class="py-2 pr-2">
                                            <span x-text="line.label"></span>
                                            <span class="block text-xs text-slate-400" x-show="line.quantity > 1"
                                                  x-text="`${line.quantity} × ${money(line.unit_amount_cents)}`"></span>
                                        </td>
                                        <td class="py-2 text-right tabular-nums" :class="line.amount_cents < 0 && 'text-emerald-600'" x-text="line.amount_formatted"></td>
                                    </tr>
                                </template>
                            </tbody>
                            <tfoot class="border-t border-slate-200">
                                <tr><td class="pt-3">Net</td><td class="pt-3 text-right tabular-nums" x-text="result.formatted.net"></td></tr>
                                <tr x-show="result.include_vat"><td>VAT</td><td class="text-right tabular-nums" x-text="result.formatted.vat"></td></tr>
                                <tr class="font-semibold"><td class="pt-1">Total</td><td class="pt-1 text-right tabular-nums" x-text="result.formatted.total"></td></tr>
                            </tfoot>
                        </table>
                    </div>
                </template>
            </div>

            <form method="POST" action="{{ route('quotes.store') }}" class="space-y-3 rounded-xl border border-slate-200 bg-white p-6">
                @csrf
                <h2 class="font-semibold">Request a formal quote</h2>
                <p class="text-sm text-slate-500">We'll email you a PDF and get back within one working day.</p>

                @if ($errors->any())
                    <ul class="space-y-1 rounded-lg bg-rose-50 p-3 text-sm text-rose-700">
                        @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                @endif

                <input type="hidden" name="product_id" :value="productId">
                <input type="hidden" name="material_id" :value="materialId">
                <input type="hidden" name="width_mm" :value="width">
                <input type="hidden" name="height_mm" :value="height">
                <input type="hidden" name="quantity" :value="quantity">
                <input type="hidden" name="include_vat" :value="includeVat ? 1 : 0">
                <template x-for="id in optionIds" :key="id"><input type="hidden" name="option_ids[]" :value="id"></template>

                <input name="name" value="{{ old('name') }}" required maxlength="120" placeholder="Your name"
                       class="w-full rounded-lg px-3 py-2 ring-1 ring-slate-200 focus:ring-indigo-400">
                <input name="email" type="email" value="{{ old('email') }}" required maxlength="190" placeholder="Email"
                       class="w-full rounded-lg px-3 py-2 ring-1 ring-slate-200 focus:ring-indigo-400">
                <input name="company" value="{{ old('company') }}" maxlength="190" placeholder="Company (optional)"
                       class="w-full rounded-lg px-3 py-2 ring-1 ring-slate-200 focus:ring-indigo-400">
                <textarea name="notes" rows="2" maxlength="2000" placeholder="Notes: deadline, artwork, installation… (optional)"
                          class="w-full rounded-lg px-3 py-2 ring-1 ring-slate-200 focus:ring-indigo-400">{{ old('notes') }}</textarea>

                <button type="submit" :disabled="!result || Object.keys(errors).length > 0"
                        class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 font-medium text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50">
                    Request quote
                </button>
            </form>
        </div>
    </aside>
</div>

<script>
    function calculator(products, initial) {
        const symbol = @js($currencySymbol);

        return {
            products,
            productId: null,
            materialId: null,
            width: null,
            height: null,
            quantity: 1,
            optionIds: [],
            includeVat: initial.includeVat,
            result: null,
            errors: {},
            loading: false,
            timer: null,
            controller: null,

            get product() {
                return this.products.find((p) => p.id === this.productId) ?? null;
            },

            init() {
                const start = this.products.find((p) => p.id === Number(initial.productId)) ?? this.products[0];
                if (!start) return;
                this.selectProduct(start.id);
                if (start.id === Number(initial.productId)) {
                    this.materialId = Number(initial.materialId) || this.materialId;
                    this.width = Number(initial.width) || this.width;
                    this.height = Number(initial.height) || this.height;
                    this.quantity = Number(initial.quantity) || 1;
                    this.optionIds = initial.optionIds;
                }
                ['productId', 'materialId', 'width', 'height', 'quantity', 'optionIds', 'includeVat']
                    .forEach((key) => this.$watch(key, () => this.schedule()));
                this.calculate();
            },

            selectProduct(id) {
                const p = this.products.find((x) => x.id === id);
                const clamp = (v, min, max) => Math.min(Math.max(v, min), max);
                this.productId = id;
                this.materialId = p.materials[0]?.id ?? null;
                this.width = clamp(600, p.size_limits_mm.min_width, p.size_limits_mm.max_width);
                this.height = clamp(400, p.size_limits_mm.min_height, p.size_limits_mm.max_height);
                this.optionIds = [];
            },

            schedule() {
                clearTimeout(this.timer);
                this.timer = setTimeout(() => this.calculate(), 250);
            },

            async calculate() {
                this.controller?.abort();
                this.controller = new AbortController();
                this.loading = true;
                try {
                    const response = await fetch(@js(route('calculator.price')), {
                        method: 'POST',
                        signal: this.controller.signal,
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        },
                        body: JSON.stringify({
                            product_id: this.productId,
                            material_id: this.materialId,
                            width_mm: this.width,
                            height_mm: this.height,
                            quantity: this.quantity,
                            option_ids: this.optionIds,
                            include_vat: this.includeVat,
                        }),
                    });
                    const json = await response.json();
                    if (response.ok) {
                        this.result = json;
                        this.errors = {};
                    } else {
                        this.errors = json.errors ?? { general: [json.message ?? 'Something went wrong.'] };
                    }
                } catch (e) {
                    if (e.name !== 'AbortError') this.errors = { general: ['Could not reach the server.'] };
                } finally {
                    this.loading = false;
                }
            },

            money(cents) {
                return symbol + (cents / 100).toLocaleString('en-GB', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            },

            optionHint(o) {
                if (o.type === 'percent') return `+${o.value / 100}%`;
                if (o.type === 'per_unit') return `+${this.money(o.value)}/pc`;
                return `+${this.money(o.value)} once`;
            },
        };
    }
</script>
@endsection
