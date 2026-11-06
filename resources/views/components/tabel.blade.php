<div class="w-full overflow-x-auto rounded-sm border border-garis bg-permukaan">
    <table {{ $attributes->merge(['class' => 'w-full border-collapse text-left text-sm [&_th]:whitespace-nowrap [&_th]:bg-latar [&_th]:px-4 [&_th]:py-3 [&_th]:font-semibold [&_td]:px-4 [&_td]:py-3 [&_td]:align-top [&_tbody_tr]:border-b [&_tbody_tr:last-child]:border-0']) }}>
        {{ $slot }}
    </table>
</div>