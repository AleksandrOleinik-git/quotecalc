@php use App\Support\Money; @endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Quote {{ $quote->reference }}</title>
    <style>
        @page { margin: 32px 40px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        h2 { font-size: 13px; margin: 18px 0 2px; }
        .muted { color: #64748b; }
        .header { width: 100%; margin-bottom: 24px; }
        .header td { vertical-align: top; }
        table.lines { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.lines td { padding: 5px 0; border-bottom: 1px solid #e2e8f0; }
        .num { text-align: right; white-space: nowrap; }
        table.totals { width: 45%; margin-left: 55%; margin-top: 18px; border-collapse: collapse; }
        table.totals td { padding: 4px 0; }
        .grand td { font-size: 14px; font-weight: bold; border-top: 2px solid #1e293b; padding-top: 6px; }
        .footer { margin-top: 36px; font-size: 9px; color: #64748b; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td>
                <h1>Quote {{ $quote->reference }}</h1>
                <div class="muted">Issued {{ $quote->created_at->format('j M Y') }} · valid until {{ $quote->validUntil()->format('j M Y') }}</div>
            </td>
            <td class="num">
                <strong>{{ config('quotecalc.company.name') }}</strong><br>
                <span class="muted">{{ config('quotecalc.company.address') }}<br>{{ config('quotecalc.company.email') }}</span>
            </td>
        </tr>
    </table>

    <div>
        <strong>Prepared for</strong><br>
        {{ $quote->customer_name }}@if ($quote->customer_company), {{ $quote->customer_company }}@endif<br>
        <span class="muted">{{ $quote->customer_email }}</span>
    </div>

    @foreach ($quote->items as $item)
        <h2>{{ $item->product_name }}</h2>
        <div class="muted">
            {{ $item->material_name }} · {{ $item->width_mm }} × {{ $item->height_mm }} mm · {{ $item->quantity }} pcs
            @if ($item->option_names) · {{ implode(', ', $item->option_names) }} @endif
        </div>
        <table class="lines">
            @foreach ($item->breakdown['lines'] as $line)
                <tr>
                    <td>{{ $line['label'] }}@if (($line['quantity'] ?? 0) > 1) <span class="muted">({{ $line['quantity'] }} × {{ Money::format((int) $line['unit_amount_cents']) }})</span>@endif</td>
                    <td class="num">{{ Money::format($line['amount_cents']) }}</td>
                </tr>
            @endforeach
            <tr>
                <td><strong>Line total (net)</strong> <span class="muted">· {{ Money::format($item->unit_net_cents) }} per piece</span></td>
                <td class="num"><strong>{{ Money::format($item->net_cents) }}</strong></td>
            </tr>
        </table>
    @endforeach

    <table class="totals">
        <tr><td>Net</td><td class="num">{{ Money::format($quote->net_cents) }}</td></tr>
        @if ($quote->include_vat)
            <tr><td>VAT {{ $quote->vat_rate_bps / 100 }}%</td><td class="num">{{ Money::format($quote->vat_cents) }}</td></tr>
        @endif
        <tr class="grand"><td>Total {{ $quote->include_vat ? '' : '(excl. VAT)' }}</td><td class="num">{{ Money::format($quote->total_cents) }}</td></tr>
    </table>

    @if ($quote->notes)
        <p><strong>Notes:</strong> {{ $quote->notes }}</p>
    @endif

    <p class="footer">
        Prices are calculated from the catalogue at the time of the request and are fixed for this quote until the validity date.
        Artwork must be supplied print-ready unless an artwork option is included.
    </p>
</body>
</html>
