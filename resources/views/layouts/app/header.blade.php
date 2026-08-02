@php use App\Models\Member;use App\Models\Payment; @endphp
@php @endphp
    <!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() == 'fa' ? 'rtl' : 'ltr' }}"
      class="dark">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen bg-white dark:bg-zinc-800">
<flux:header container class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
    <flux:sidebar.toggle class="sm:hidden mr-2" icon="bars-2" inset="left"/>

    <x-app-logo href="{{ auth()->user()->role == 'admin' ? route('admin.dashboard') : route('member.dashboard') }}"
                wire:navigate/>

    <flux:navbar class="-mb-px max-sm:hidden">
        @if(auth()->user()->role == 'admin')
            <flux:navbar.item icon="user">
                {{ __('Members') }} :
                {{ Member::query()->where('club_id', auth()->user()->club_id)->count() }}
            </flux:navbar.item>

            <flux:navbar.item
                icon="plus"
                wire:navigate
                x-on:click="$dispatch('open-create-member')"
            >
                {{ __('Create Member') }}
            </flux:navbar.item>
        @endif
    </flux:navbar>

    <flux:spacer/>

    <flux:navbar class="-mb-px max-sm:hidden">
        @if(auth()->user()->role == 'admin')
            <flux:navbar.item icon="calendar-days">
                {{ jdate()->format('l d F Y') }}
            </flux:navbar.item>
        @endif
    </flux:navbar>

    <flux:spacer/>

    <flux:navbar class="-mb-px max-sm:hidden">
        @if(auth()->user()->role == 'admin')
            <flux:navbar.item
                icon="exclamation-triangle"
                wire:navigate
                x-on:click="$dispatch('debtor-toggle')"
            >
                {{ __('Debtors') }} :
                {{
                    Member::query()
                        ->where('club_id', auth()->user()->club_id)
                        ->whereDoesntHave('payments', function ($query) {
                            $query->where('year', jdate()->getYear())
                            ->where('month', jdate()->getMonth());
                        })
                        ->count()
                }}
            </flux:navbar.item>

            <flux:navbar.item icon="banknotes">
                درآمد :
                {{
                    number_format(
                        Payment::query()
                            ->whereHas('member', function ($query) {
                                $query->where('club_id', auth()->user()->club_id);
                            })
                            ->where('year', jdate()->getYear())
                            ->where('month', jdate()->getMonth())
                            ->sum('amount')
                    )
                }}
            </flux:navbar.item>
        @endif
    </flux:navbar>

    <x-desktop-user-menu/>
</flux:header>

<!-- Mobile Menu -->
<flux:sidebar collapsible="mobile" sticky
              class="sm:hidden border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
    <flux:sidebar.header>
        <x-app-logo
            :sidebar="true"
            href="{{ auth()->user()->role == 'admin' ? route('admin.dashboard') : route('member.dashboard') }}"
            wire:navigate/>
        <flux:sidebar.collapse
            class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2"/>
    </flux:sidebar.header>

    <flux:sidebar.nav>
        <flux:sidebar.group>
            <flux:sidebar.item icon="user-plus" class="{{ auth()->user()->role == 'admin' ? '' : 'hidden' }}"
                               wire:navigate
                               x-on:click="$dispatch('open-create-member')">
                {{ __('Create Member') }}
            </flux:sidebar.item>
        </flux:sidebar.group>
    </flux:sidebar.nav>

    <flux:spacer/>
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
