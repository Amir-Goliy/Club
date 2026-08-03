<?php

use App\Models\Member;
use App\Models\Payment;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;
    use WithFileUploads;

    public ?Member $editing = null;
    public ?Member $deleting = null;

    public $image = null;
    public ?string $oldImage = null;

    public ?string $first_name = null;
    public ?string $last_name = null;
    public ?string $national_code = null;
    public ?string $birth_date = null;
    public ?string $phone = null;

    public string $search = '';

    public ?int $amount = null;
    public int $payment_month = 1;

    public bool $debtors = false;

    public function mount(): void
    {
        abort_unless(auth()->user()->role === 'admin', 403);
    }

    protected function rules(): array
    {
        return [
            'image' => [
                'nullable',
                'image',
                'max:2048',
            ],

            'first_name' => [
                'required',
                'string',
                'max:255',
                'not_regex:/\d/',
            ],

            'last_name' => [
                'required',
                'string',
                'max:255',
                'not_regex:/\d/',
            ],

            'national_code' => [
                'required',
                'digits:10',
                Rule::unique('members', 'national_code')
                    ->ignore($this->editing?->id),
            ],

            'birth_date' => [
                'nullable',
                'date',
            ],

            'phone' => [
                'nullable',
                'digits:11',
            ],

            'amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'payment_month' => [
                'required',
                'integer',
                'between:1,12',
            ],
        ];
    }

    public function getMembersProperty(): LengthAwarePaginator
    {
        return Member::query()
            ->where('club_id', auth()->user()->club_id)
            ->with([
                'payments' => fn($query) => $query
                    ->where('year', jdate()->getYear())
                    ->where('month', jdate()->getMonth())
            ])
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query
                        ->where('first_name', 'like', "%{$this->search}%")
                        ->orWhere('last_name', 'like', "%{$this->search}%")
                        ->orWhere('national_code', 'like', "%{$this->search}%")
                        ->orWhere('phone', 'like', "%{$this->search}%");
                });
            })
            ->when($this->debtors, function ($query) {
                $query->whereDoesntHave('payments', function ($query) {
                    $query
                        ->where('year', jdate()->getYear())
                        ->where('month', jdate()->getMonth());
                });
            })
            ->latest('id')
            ->paginate(10);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[On('open-create-member')]
    public function openCreateUser(): void
    {
        $this->resetForm();

        $this->payment_month = jdate()->getMonth();

        $this->modal('member-modal')->show();
    }

    #[On('debtor-toggle')]
    public function debtorToggle(bool $value): void
    {
        $this->debtors = $value;
        $this->resetPage();
    }

    public function edit(Member $member): void
    {
        abort_unless($member->club_id === auth()->user()->club_id, 403);

        $this->resetForm();

        $this->editing = $member;
        $this->oldImage = $member->image;

        $this->fill(
            $member->only([
                'first_name',
                'last_name',
                'national_code',
                'birth_date',
                'phone',
            ])
        );

        $this->payment_month = jdate()->getMonth();

        $this->updatedPaymentMonth();

        $this->modal('member-modal')->show();
    }

    public function updatedPaymentMonth(): void
    {
        if (!$this->editing) {
            return;
        }

        $this->amount = $this->editing
            ->payments()
            ->where('year', jdate()->getYear())
            ->where('month', $this->payment_month)
            ->value('amount');
    }

    public function store(): void
    {
        $validated = $this->validate();

        $memberData = collect($validated)
            ->except(['amount', 'payment_month'])
            ->toArray();

        $memberData['club_id'] = auth()->user()->club_id;

        if ($image = $this->saveImage()) {
            $memberData['image'] = $image;
        } else {
            unset($memberData['image']);
        }

        $member = Member::query()->create($memberData);

        $this->savePayment($member);

        $this->closeMemberModal(__('Member saved.'));
    }

    public function update(): void
    {
        abort_unless($this->editing?->club_id === auth()->user()->club_id, 403);

        $validated = $this->validate();

        $memberData = collect($validated)
            ->except(['amount', 'payment_month', 'image'])
            ->toArray();

        if ($image = $this->saveImage()) {
            $memberData['image'] = $image;
        }

        $this->editing->update($memberData);

        $this->savePayment($this->editing);

        $this->closeMemberModal(__('Member updated.'));
    }

    protected function saveImage(): ?string
    {
        if (!$this->image) {
            return null;
        }

        if ($this->editing?->image) {
            Storage::disk('public')
                ->delete($this->editing->image);
        }

        return $this->image->store('image', 'public');
    }

    protected function savePayment(Member $member): void
    {
        if (is_null($this->amount)) {
            return;
        }

        Payment::query()->updateOrCreate(
            [
                'member_id' => $member->id,
                'year' => jdate()->getYear(),
                'month' => $this->payment_month,
            ],
            [
                'amount' => $this->amount,
                'paid_at' => now(),
            ]
        );
    }

    public function confirmDelete(Member $member): void
    {
        abort_unless($member->club_id === auth()->user()->club_id, 403);

        $this->deleting = $member;

        $this->modal('delete-member-modal')->show();
    }

    public function delete(): void
    {
        if (!$this->deleting) {
            return;
        }

        if ($this->deleting->image) {
            Storage::disk('public')
                ->delete($this->deleting->image);
        }

        $this->deleting->delete();

        $this->modal('delete-member-modal')->close();

        Flux::toast(
            text: __('Member deleted.'),
            variant: 'success'
        );

        $this->deleting = null;
    }

    protected function closeMemberModal(string $message): void
    {
        $this->modal('member-modal')->close();

        Flux::toast(
            text: $message,
            variant: 'success'
        );

        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->reset([
            'editing',
            'image',
            'oldImage',
            'first_name',
            'last_name',
            'national_code',
            'birth_date',
            'phone',
            'amount',
        ]);

        $this->clearValidation();
    }
};

?>

<div>
    <div class="flex justify-center mb-6">
        <div class="w-full max-w-50">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="{{ __('Search') }}"
                clearable
            />
        </div>
    </div>

    <flux:table
        class="text-center"
        :paginate="$this->members"
    >
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
                {{ __('Actions') }}
            </flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse($this->members as $member)
                <flux:table.row wire:key="member-{{ $member->id }}">
                    <flux:table.cell class="flex justify-center items-center">
                        @if($member->image)
                            <img
                                src="{{ Storage::url($member->image) }}"
                                class="w-15 sm:w-30 rounded-xl object-cover"
                                alt="{{ $member->first_name }}"
                            >
                        @else
                            <div
                                class="w-15 h-15 rounded-xl bg-zinc-100 dark:bg-zinc-700 flex items-center justify-center">
                                -
                            </div>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell>{{ $member->first_name }}</flux:table.cell>

                    <flux:table.cell>{{ $member->last_name }}</flux:table.cell>

                    <flux:table.cell>{{ $member->national_code }}</flux:table.cell>

                    <flux:table.cell>{{ $member->birth_date }}</flux:table.cell>

                    <flux:table.cell>{{ $member->phone }}</flux:table.cell>

                    <flux:table.cell>
                        @if($member->payments->isNotEmpty())
                            <flux:badge color="green">{{ __('Paid') }}</flux:badge>
                        @else
                            <flux:badge color="red">{{ __('Unpaid') }}</flux:badge>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell>
                        <div class="flex gap-2 justify-center">
                            <flux:button
                                wire:click="edit({{ $member->id }})"
                                wire:loading.attr="disabled"
                            >
                                {{ __('Edit') }}
                            </flux:button>

                            <flux:button
                                wire:click="confirmDelete({{ $member->id }})"
                                variant="danger"
                            >
                                {{ __('Delete') }}
                            </flux:button>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="8">
                        {{ __('No members found.') }}
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>


    <flux:modal
        name="member-modal"
        :flyout="true"
        position="left"
        :dismissible="false"
    >
        <div class="space-y-4">
            <flux:heading>
                {{ $editing ? __('Edit Member') : __('Create Member') }}
            </flux:heading>

            <flux:heading>{{ __('Member image') }}</flux:heading>

            <flux:input
                type="file"
                accept=".png,.jpg,.jpeg"
                wire:model="image"
            />

            @if($image)
                <div class="flex justify-center">
                    <img
                        src="{{ $image->temporaryUrl() }}"
                        class="w-20 sm:w-80 rounded-xl object-cover"
                        alt=""
                    >
                </div>
            @elseif($oldImage)
                <div class="flex justify-center">
                    <img
                        src="{{ Storage::url($oldImage) }}"
                        class="w-20 sm:w-80 rounded-xl object-cover"
                        alt=""
                    >
                </div>
            @endif

            <flux:separator/>

            <flux:heading>{{ __('Member information') }}</flux:heading>
            <flux:input
                wire:model="first_name"
                label="{{ __('First Name') }}"
            />

            <flux:input
                wire:model="last_name"
                label="{{ __('Last Name') }}"
            />

            <flux:input
                wire:model="national_code"
                label="{{ __('National Code') }}"
            />

            <flux:input
                mask="****-**-**"
                wire:model="birth_date"
                label="{{ __('Birth date') }}"
            />

            <flux:input
                wire:model="phone"
                label="{{ __('Phone') }}"
            />

            <flux:separator/>

            <flux:heading>{{ __('Payment Information') }}</flux:heading>
            <flux:input type="number" wire:model="amount" label="{{ __('Amount') }}"/>

            <flux:select wire:model.live="payment_month" label="{{ __('Month') }}">
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
                @if($editing)
                    <flux:button
                        wire:click="update"
                        wire:loading.attr="disabled"
                    >
                        {{ __('Update') }}
                    </flux:button>
                @else
                    <flux:button
                        wire:click="store"
                        wire:loading.attr="disabled"
                    >
                        {{ __('Save') }}
                    </flux:button>
                @endif
            </div>
        </div>
    </flux:modal>

    <flux:modal name="delete-member-modal" class="max-w-md">
        <div class="space-y-4">
            <flux:heading>{{ __('Delete Member') }}</flux:heading>

            <p>
                {{ __('Are you sure you want to delete this member?') }}
            </p>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button>
                        {{ __('Cancel') }}
                    </flux:button>
                </flux:modal.close>

                <flux:button
                    wire:click="delete"
                    variant="danger"
                    wire:loading.attr="disabled"
                >
                    {{ __('Delete') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
