@php use App\Models\Member; @endphp
@php use App\Models\Payment; @endphp
    <!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() == 'fa' ? 'rtl' : 'ltr' }}"
      class="dark">
<head>
    @include('partials.head')

    <link rel="manifest" href="/manifest.json">

    <meta name="theme-color" content="#111827">

    <link rel="apple-touch-icon" href="{{ asset('icons/icon-512.png') }}">
</head>

<script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js')
            .then(() => {
                console.log('SW registered');
            });
    }
</script>

<body class="min-h-screen bg-white dark:bg-zinc-800">

@php
    $isAdmin = auth()->user()->role === 'admin';

    if ($isAdmin) {
        $clubId = auth()->user()->club_id;
        $currentYear  = jdate()->getYear();
        $currentMonth = jdate()->getMonth();

        $membersCount = Member::query()
            ->where('club_id', $clubId)
            ->count();

        $debtorsCount = Member::query()
            ->where('club_id', $clubId)
            ->whereDoesntHave('payments', function ($query) use ($currentYear, $currentMonth) {
                $query->where('year', $currentYear)
                      ->where('month', $currentMonth);
            })
            ->count();

        $monthlyIncome = Payment::query()
            ->whereHas('member', function ($query) use ($clubId) {
                $query->where('club_id', $clubId);
            })
            ->where('year', $currentYear)
            ->where('month', $currentMonth)
            ->sum('amount');

        $todayFormatted = app()->getLocale() === 'fa'
            ? jdate()->format('l d F Y')
            : now()->translatedFormat('l, F d Y');
    }
@endphp

<flux:header container class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
    <flux:sidebar.toggle class="sm:hidden mr-2" icon="bars-2" inset="left"/>

    <x-app-logo
        href="{{ $isAdmin ? route('admin.dashboard') : route('member.dashboard') }}"
        wire:navigate
    />

    @if($isAdmin)
        <flux:navbar class="-mb-px max-sm:hidden">
            <flux:navbar.item icon="user">
                {{ __('Members') }} : {{ $membersCount }}
            </flux:navbar.item>

            <flux:navbar.item
                icon="plus"
                wire:navigate
                x-on:click="$dispatch('open-create-member')"
            >
                {{ __('Create Member') }}
            </flux:navbar.item>
        </flux:navbar>
    @endif

    <flux:spacer/>

    @if($isAdmin)
        <flux:navbar class="-mb-px max-sm:hidden">
            <flux:navbar.item icon="calendar-days">
                {{ $todayFormatted }}
            </flux:navbar.item>
        </flux:navbar>
    @endif

    <flux:spacer/>

    @if($isAdmin)
        <flux:navbar class="-mb-px max-sm:hidden">
            <flux:navbar.item icon="exclamation-triangle">
                {{ __('Debtors') }} : {{ $debtorsCount }}
            </flux:navbar.item>

            <flux:checkbox
                wire:navigate
                x-on:change="$dispatch('debtor-toggle', { value: $event.target.checked })"
            />

            <flux:navbar.item icon="banknotes">
                {{ __('Income') }} : {{ number_format($monthlyIncome) }}
            </flux:navbar.item>
        </flux:navbar>
    @endif

    <x-desktop-user-menu/>
</flux:header>

<!-- Mobile Menu -->
<flux:sidebar collapsible="mobile" sticky
              class="sm:hidden border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
    <flux:sidebar.header>
        <x-app-logo
            :sidebar="true"
            href="{{ $isAdmin ? route('admin.dashboard') : route('member.dashboard') }}"
            wire:navigate
        />
        <flux:sidebar.collapse
            class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2"/>
    </flux:sidebar.header>

    @if($isAdmin)
        <flux:sidebar.nav>
            <flux:sidebar.item icon="calendar-days">
                {{ $todayFormatted }}
            </flux:sidebar.item>

            <flux:sidebar.item icon="user">
                {{ __('Members') }} : {{ $membersCount }}
            </flux:sidebar.item>

            <flux:sidebar.item icon="banknotes">
                {{ __('Income') }} : {{ number_format($monthlyIncome) }}
            </flux:sidebar.item>
        </flux:sidebar.nav>

        <flux:sidebar.spacer/>

        <flux:sidebar.nav>
            <div class="flex items-center gap-2">
                <flux:sidebar.item icon="exclamation-triangle">
                    {{ __('Debtors') }} : {{ $debtorsCount }}
                </flux:sidebar.item>

                <flux:checkbox
                    wire:navigate
                    x-on:change="$dispatch('debtor-toggle', $event.target.checked)"
                />
            </div>

            <flux:sidebar.item
                icon="plus"
                wire:navigate
                x-on:click="$dispatch('open-create-member')"
            >
                {{ __('Create Member') }}
            </flux:sidebar.item>
        </flux:sidebar.nav>
    @endif
</flux:sidebar>

{{ $slot }}

@persist('toast')
<flux:toast.group position="top start">
    <flux:toast/>
</flux:toast.group>
@endpersist

@fluxScripts
</body>
</html>
