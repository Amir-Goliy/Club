<?php

use App\Models\Member;
use App\Models\Payment;
use Illuminate\Pagination\LengthAwarePaginator;
use JetBrains\PhpStorm\NoReturn;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;
    use WithFileUploads;

    public ?Member $editing = null;

    public $image;
    public string $first_name;
    public string $last_name;
    public string $national_code;
    public string $birth_date;
    public string $phone;
    public string $search = '';

    public ?int $amount = null;
    public int $payment_month;

    public bool $debtors = false;

    public function getMembersProperty(): LengthAwarePaginator
    {
        return Member::query()
            ->where('club_id', auth()->user()->club_id)
            ->with(['payments' => function ($query) {
                $query->where('year', jdate()->getYear())
                    ->where('month', jdate()->getMonth());
            }])
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('first_name', 'like', "%{$this->search}%")
                        ->orWhere('last_name', 'like', "%{$this->search}%")
                        ->orWhere('national_code', 'like', "%{$this->search}%")
                        ->orWhere('phone', 'like', "%{$this->search}%");
                });
            })
            ->when($this->debtors, function ($query) {
                $query->whereDoesntHave('payments', function ($query) {
                    $query->where('year', jdate()->getYear())
                        ->where('month', jdate()->getMonth());
                });
            })
            ->orderByDesc('id')
            ->paginate(10);
    }

    public function updatedPaymentMonth(): void
    {
        if (!$this->editing) {
            return;
        }

        $payment = $this->editing->payments()
            ->where('year', jdate()->getYear())
            ->where('month', $this->payment_month)
            ->first();

        $this->amount = $payment?->amount;
    }

    protected function rules(): array
    {
        return [
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'first_name' => 'required|string|not_regex:/\d/',
            'last_name' => 'required|string|not_regex:/\d/',
            'national_code' => 'required|digits:10|unique:members,national_code,' . ($this->editing?->id),
            'birth_date' => 'nullable|date',
            'phone' => 'nullable|digits:11',
        ];
    }

    protected function resetForm(): void
    {
        $this->reset([
            'editing',
            'image',
            'first_name',
            'last_name',
            'national_code',
            'birth_date',
            'phone',
            'search',
            'amount',
        ]);

        $this->clearValidation();
    }

    #[On('open-create-member')]
    public function openCreateUser(): void
    {
        $this->resetForm();

        $this->payment_month = jdate()->getMonth();

        $this->modal('member-modal')->show();
    }

    #[NoReturn]
    #[On('debtor-toggle')]
    public function debtorToggle(): void
    {
        $this->debtors = !$this->debtors;
    }

    public function store(): void
    {
        $validated = $this->validate();

        $validated['club_id'] = auth()->user()->club_id;

        if ($this->image) {
            $validated['image'] = $this->image->store('image', 'public');
        }

        $member = Member::create($validated);

        if ($this->amount) {
            $member->payments()->updateOrCreate(
                [
                    'year' => jdate()->getYear(),
                    'month' => $this->payment_month,
                ],
                [
                    'amount' => $this->amount,
                    'paid_at' => now(),
                ]
            );
        }

        $this->modal('member-modal')->close();

        Flux::toast(text: __('Member saved.'), variant: 'success');

        $this->resetForm();
    }

    public function edit(Member $member): void
    {
        $this->resetForm();

        $this->editing = $member;

        $this->fill($member->only([
            'image',
            'first_name',
            'last_name',
            'national_code',
            'birth_date',
            'phone',
        ]));

        $this->payment_month = jdate()->getMonth();

        $this->updatedPaymentMonth();

        $this->modal('member-modal')->show();
    }

    public function update(): void
    {
        $validated = $this->validate();

        if ($this->image && !is_string($this->image)) {

            if (
                $this->editing->image &&
                Storage::disk('public')->exists($this->editing->image)
            ) {
                Storage::disk('public')->delete($this->editing->image);
            }

            $validated['image'] = $this->image->store('image', 'public');
        }

        $this->editing->update($validated);

        if ($this->amount) {

            Payment::query()->updateOrCreate([
                'member_id' => $this->editing->id,
                'year' => jdate()->getYear(),
                'month' => $this->payment_month,
                'amount' => $this->amount,
            ]);
        }

        $this->modal('member-modal')->close();

        Flux::toast(text: __('Member updated.'), variant: 'success');

        $this->resetForm();
    }

    public function delete(Member $member): void
    {
        if ($member->image)
            Storage::disk('public')->delete($member->image);

        $member->delete();

        Flux::toast(text: __('Member deleted.'), variant: 'success');
    }
};
?>

<div>
    <div class="flex justify-center mb-6">
        <div class="w-full max-w-50">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="{{ __('Search') }}"
                clearable="true"
            />
        </div>
    </div>

    <div>
        <flux:table class="text-center" :paginate="$this->members">
            <flux:table.columns>
                <flux:table.column align="center">
                    {{ __('Image') }}
                </flux:table.column>

                <flux:table.column align="center">
                    {{ __('First Name') }}
                </flux:table.column>

                <flux:table.column align="center">
                    {{ __('Last Name') }}
                </flux:table.column>

                <flux:table.column align="center">
                    {{ __('National Code') }}
                </flux:table.column>

                <flux:table.column align="center">
                    {{ __('Birth date') }}
                </flux:table.column>

                <flux:table.column align="center">
                    {{ __('Phone') }}
                </flux:table.column>

                <flux:table.column align="center">
                    {{ __('Payment') }}
                </flux:table.column>

                <flux:table.column align="center">
                    {{ __('Edit') }}
                </flux:table.column>

                <flux:table.column align="center">
                    {{ __('Delete') }}
                </flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach($this->members as $member)
                    <flux:table.row>
                        <flux:table.cell class="flex justify-center items-center">
                            <img
                                src="{{ Storage::url($member->image) }}"
                                class="w-15 sm:w-30 rounded-xl object-cover"
                                alt=""
                            >
                        </flux:table.cell>

                        <flux:table.cell>
                            {{ $member->first_name }}
                        </flux:table.cell>

                        <flux:table.cell>
                            {{ $member->last_name }}
                        </flux:table.cell>

                        <flux:table.cell>
                            {{ $member->national_code }}
                        </flux:table.cell>

                        <flux:table.cell>
                            {{ $member->birth_date }}
                        </flux:table.cell>

                        <flux:table.cell>
                            {{ $member->phone }}
                        </flux:table.cell>

                        <flux:table.cell>
                            @php
                                $payment = $member->payments->first();
                            @endphp

                            @if($payment)
                                <flux:badge color="green">
                                    {{ __('Paid') }}
                                </flux:badge>
                            @else
                                <flux:badge color="red">
                                    {{ __('Unpaid') }}
                                </flux:badge>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:button wire:click="edit({{ $member->id }})">
                                {{ __('Edit') }}
                            </flux:button>
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:button
                                wire:click="delete({{ $member->id }})"
                                variant="primary"
                            >
                                {{ __('Delete') }}
                            </flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal
        :flyout="true"
        position="left"
        :dismissible="false"
        name="member-modal"
    >
        <div class="space-y-4">
            <flux:heading>
                {{ $editing ? __('Edit Member') : __('Create Member') }}
            </flux:heading>

            <flux:heading>
                {{ __('Member image') }}
            </flux:heading>

            <flux:input
                type="file"
                accept=".png,.jpg,.jpeg"
                wire:model.live="image"
            />

            @if ($image)
                <div class="flex justify-center items-center">
                    <img
                        src="{{ is_string($image) ? Storage::url($image) : $image->temporaryUrl() }}"
                        class="w-20 sm:w-80 rounded-xl object-cover"
                        alt=""
                    >
                </div>
            @endif

            <flux:separator/>

            <flux:heading>
                {{ __('Member information') }}
            </flux:heading>

            <flux:input wire:model.live="first_name" label="{{ __('First Name') }}"/>

            <flux:input wire:model.live="last_name" label="{{ __('Last Name') }}"/>

            <flux:input wire:model.live="national_code" label="{{ __('National Code') }}"/>

            <flux:input mask="****-**-**" wire:model.live="birth_date" label="{{ __('Birth date') }}"/>

            <flux:input wire:model.live="phone" label="{{ __('Phone') }}"/>

            <flux:separator/>

            <flux:heading>
                {{ __('Payment Information') }}
            </flux:heading>

            <flux:input
                type="number"
                wire:model.live="amount"
                :label="__('Amount')"
            />

            <flux:select
                wire:model.live="payment_month"
                :label="__('Month')"
            >
                @if(app()->getLocale() === 'fa')
                    <option value="1">فروردین</option>
                    <option value="2">اردیبهشت</option>
                    <option value="3">خرداد</option>
                    <option value="4">تیر</option>
                    <option value="5">مرداد</option>
                    <option value="6">شهریور</option>
                    <option value="7">مهر</option>
                    <option value="8">آبان</option>
                    <option value="9">آذر</option>
                    <option value="10">دی</option>
                    <option value="11">بهمن</option>
                    <option value="12">اسفند</option>
                @else
                    <option value="1">January</option>
                    <option value="2">February</option>
                    <option value="3">March</option>
                    <option value="4">April</option>
                    <option value="5">May</option>
                    <option value="6">June</option>
                    <option value="7">July</option>
                    <option value="8">August</option>
                    <option value="9">September</option>
                    <option value="10">October</option>
                    <option value="11">November</option>
                    <option value="12">December</option>
                @endif
            </flux:select>

            <div class="flex justify-end">
                <flux:button wire:click="{{ $editing ? 'update' : 'store' }}">
                    {{ __('Save') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
