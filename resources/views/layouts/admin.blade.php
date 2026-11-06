<x-layout.admin :title="trim(view()->yieldContent('title', 'Admin'))">
    @yield('content')
</x-layout.admin>
