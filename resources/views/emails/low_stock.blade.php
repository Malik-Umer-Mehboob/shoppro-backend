<x-mail::message>
# Low Stock Alert

The following items are running low on stock and require your attention.

<x-mail::table>
| Product Name | SKU | Current Stock | Threshold |
| :--- | :--- | :--- | :--- |
@foreach($lowStockData as $item)
| {{ $item['name'] }} | {{ $item['sku'] }} | **{{ $item['stock'] }}** | {{ $item['threshold'] }} |
@endforeach
</x-mail::table>

<x-mail::button :url="config('app.frontend_url') . '/admin/low-stock'" color="orange">
View All Low Stock Items
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
