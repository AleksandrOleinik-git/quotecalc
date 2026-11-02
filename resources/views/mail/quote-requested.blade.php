@php use App\Support\Money; @endphp
<x-mail::message>
# New quote request {{ $quote->reference }}

**{{ $quote->customer_name }}**@if ($quote->customer_company) ({{ $quote->customer_company }})@endif — {{ $quote->customer_email }}

<x-mail::table>
| Item | Qty | Net |
|:-----|----:|----:|
@foreach ($quote->items as $item)
| {{ $item->product_name }}, {{ $item->material_name }}, {{ $item->width_mm }}×{{ $item->height_mm }} mm | {{ $item->quantity }} | {{ Money::format($item->net_cents) }} |
@endforeach
</x-mail::table>

**Total: {{ Money::format($quote->total_cents) }}** {{ $quote->include_vat ? 'incl. VAT' : 'excl. VAT' }}

@if ($quote->notes)
> {{ $quote->notes }}
@endif

<x-mail::button :url="url('/admin/quotes/'.$quote->reference)">
Open in admin
</x-mail::button>

The PDF quote is attached.
</x-mail::message>
