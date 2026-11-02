@extends('layouts.app')

@section('title', "Quote {$quote->reference}")

@php use App\Support\Money; @endphp

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5 text-emerald-800">
        <p class="font-medium">Thank you, {{ $quote->customer_name }}! Your quote request has been received.</p>
        <p class="mt-1 text-sm">Reference <strong>{{ $quote->reference }}</strong>. Our team will contact you at {{ $quote->customer_email }}.</p>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold">Quote {{ $quote->reference }}</h1>
                <p class="text-sm text-slate-500">Issued {{ $quote->created_at->format('j M Y') }} · valid until {{ $quote->validUntil()->format('j M Y') }}</p>
            </div>
            <a href="{{ $pdfUrl }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Download PDF</a>
        </div>

        @foreach ($quote->items as $item)
            <div class="mt-6">
                <h2 class="font-medium">{{ $item->product_name }}</h2>
                <p class="text-sm text-slate-500">
                    {{ $item->material_name }} · {{ $item->width_mm }} × {{ $item->height_mm }} mm · {{ $item->quantity }} pcs
                    @if ($item->option_names) · {{ implode(', ', $item->option_names) }} @endif
                </p>
                <table class="mt-3 w-full text-sm">
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($item->breakdown['lines'] as $line)
                            <tr>
                                <td class="py-2">{{ $line['label'] }}</td>
                                <td class="py-2 text-right tabular-nums">{{ Money::format($line['amount_cents']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach

        <dl class="mt-6 space-y-1 border-t border-slate-200 pt-4 text-sm">
            <div class="flex justify-between"><dt>Net</dt><dd class="tabular-nums">{{ Money::format($quote->net_cents) }}</dd></div>
            @if ($quote->include_vat)
                <div class="flex justify-between"><dt>VAT {{ $quote->vat_rate_bps / 100 }}%</dt><dd class="tabular-nums">{{ Money::format($quote->vat_cents) }}</dd></div>
            @endif
            <div class="flex justify-between text-base font-semibold"><dt>Total</dt><dd class="tabular-nums">{{ Money::format($quote->total_cents) }}</dd></div>
        </dl>
    </div>

    <a href="{{ route('calculator') }}" class="inline-block text-sm text-indigo-600 hover:underline">← Price another item</a>
</div>
@endsection
