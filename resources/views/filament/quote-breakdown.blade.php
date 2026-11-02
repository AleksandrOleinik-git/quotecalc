@php
    use App\Support\Money;
    /** @var \App\Models\Quote $quote */
    $quote = $getRecord();
@endphp
<div class="space-y-6 text-sm">
    @foreach ($quote->items as $item)
        <div>
            <div class="font-semibold">{{ $item->product_name }}</div>
            <div class="text-gray-500">
                {{ $item->material_name }} · {{ $item->width_mm }} × {{ $item->height_mm }} mm · {{ $item->quantity }} pcs
                @if ($item->option_names) · {{ implode(', ', $item->option_names) }} @endif
            </div>
            <table class="mt-2 w-full">
                @foreach ($item->breakdown['lines'] as $line)
                    <tr class="border-b border-gray-100 dark:border-white/5">
                        <td class="py-1.5">{{ $line['label'] }}</td>
                        <td class="py-1.5 text-gray-500">{{ $line['type'] }}</td>
                        <td class="py-1.5 text-right tabular-nums">{{ Money::format($line['amount_cents']) }}</td>
                    </tr>
                @endforeach
                <tr class="font-medium">
                    <td class="py-1.5">Line net ({{ Money::format($item->unit_net_cents) }}/pc)</td><td></td>
                    <td class="py-1.5 text-right tabular-nums">{{ Money::format($item->net_cents) }}</td>
                </tr>
            </table>
        </div>
    @endforeach

    <div class="ml-auto w-64 space-y-1 border-t pt-3 dark:border-white/10">
        <div class="flex justify-between"><span>Net</span><span class="tabular-nums">{{ Money::format($quote->net_cents) }}</span></div>
        <div class="flex justify-between"><span>VAT {{ $quote->include_vat ? ($quote->vat_rate_bps / 100).'%' : '(not included)' }}</span><span class="tabular-nums">{{ Money::format($quote->vat_cents) }}</span></div>
        <div class="flex justify-between font-semibold"><span>Total</span><span class="tabular-nums">{{ Money::format($quote->total_cents) }}</span></div>
    </div>
</div>
