<?php

use App\Models\Member;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public Member $member;

    public bool $editing = false;

    public $image = null;

    public ?string $phone = null;

    public function mount(): void
    {
        $this->member = auth()->user()->member()->with('club')->firstOrFail();

        $this->loadForm();
    }

    protected function rules(): array
    {
        return [
            'phone' => [
                'nullable',
                'digits:11',
            ],

            'image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png',
                'max:2048',
            ],
        ];
    }

    public function getCurrentYearPaymentsProperty(): Collection
    {
        return $this->member
            ->payments()
            ->where('year', jdate()->getYear())
            ->orderByDesc('month')
            ->get();
    }

    public function startEditing(): void
    {
        $this->editing = true;
    }

    public function cancel(): void
    {
        $this->editing = false;

        $this->reset(['image']);

        $this->loadForm();

        $this->resetValidation();
    }

    public function update(): void
    {
        $validated = $this->validate();

        unset($validated['image']);

        if ($this->image) {
            $validated['image'] = $this->updateImage();
        }

        $this->member->update($validated);

        $this->member->refresh();

        $this->editing = false;
        $this->image = null;

        Flux::toast(
            text: __('Profile updated.'),
            variant: 'success'
        );
    }

    protected function updateImage(): string
    {
        $oldImage = $this->member->image;

        $newImage = $this->image->store('image', 'public');

        if ($oldImage) {
            Storage::disk('public')->delete($oldImage);
        }

        return $newImage;
    }

    protected function loadForm(): void
    {
        $this->phone = $this->member->phone;
    }
};

?>

<div class="max-w-sm sm:max-w-2xl mx-auto space-y-6">
    <flux:card>
        <div class="flex flex-col items-center gap-4">
            @if($image)
                <img
                    src="{{ $image->temporaryUrl() }}"
                    class="w-40 h-40 rounded-full object-cover"
                    alt=""
                >
            @elseif($member->image)
                <img
                    src="{{ Storage::url($member->image) }}"
                    class="w-40 h-40 rounded-full object-cover"
                    alt=""
                >
            @endif

            @if($editing)
                <flux:input
                    type="file"
                    accept=".png,.jpg,.jpeg"
                    wire:model="image"
                    label="{{ __('Profile image') }}"
                />
            @endif

            <h1 class="text-2xl font-bold">
                {{ $member->first_name }} {{ $member->last_name }}
            </h1>

            <p>{{ __('Club member') }} {{ $member->club->name }}</p>
        </div>
    </flux:card>

    <flux:card>
        <flux:heading class="mb-6">{{ __('Personal information') }}</flux:heading>

        <div class="grid sm:grid-cols-2 gap-6">
            <div>
                <span class="text-sm text-zinc-700 dark:text-zinc-300">
                    {{ __('National code') }}
                </span>
                <p>{{ $member->national_code }}</p>
            </div>

            <div>
                <span class="text-sm text-zinc-700 dark:text-zinc-300">
                    {{ __('Joined at') }}
                </span>
                <p>
                    {{ app()->getLocale() === 'fa'
                        ? jdate($member->created_at)->format('Y/m/d')
                        : $member->created_at->format('F d, Y')
                    }}
                </p>
            </div>

            <div>
                <span class="text-sm text-zinc-700 dark:text-zinc-300">
                    {{ __('Phone') }}
                </span>

                @if($editing)
                    <flux:input wire:model="phone"/>
                @else
                    <p>{{ $member->phone }}</p>
                @endif
            </div>

            <div>
                <span class="text-sm text-zinc-700 dark:text-zinc-300">
                    {{ __('Birth date') }}
                </span>
                <p>
                    {{ app()->getLocale() === 'fa'
                        ? jdate($member->birth_date)->format('Y/m/d')
                        : Carbon::parse($member->birth_date)->format('F d, Y')
                    }}
                </p>
            </div>
        </div>
    </flux:card>

    <flux:card>
        <flux:heading class="mb-4">{{ __('Payment History') }}</flux:heading>

        @forelse($this->currentYearPayments as $payment)
            <div class="flex justify-between items-center border-b py-3" wire:key="payment-{{ $payment->id }}">
                <div>
                    <p>
                        {{ app()->getLocale() === 'fa'
                            ? $payment->month_name
                            : Carbon::create()->month($payment->month)->format('F')
                        }}
                    </p>

                    <small class="text-zinc-700 dark:text-zinc-300">
                        {{ $payment->amount }} {{ __('Dollar') }}
                    </small>
                </div>

                <flux:badge color="green">{{ __('Paid') }}</flux:badge>
            </div>
        @empty
            <p class="text-zinc-700 dark:text-zinc-300">
                {{ __('No payment has been recorded yet.') }}
            </p>
        @endforelse
    </flux:card>

    <div class="flex gap-3">
        @if(!$editing)
            <flux:button wire:click="startEditing" variant="primary">
                {{ __('Edit profile') }}
            </flux:button>
        @else
            <flux:button wire:click="cancel" variant="ghost">
                {{ __('Cancel') }}
            </flux:button>

            <flux:button wire:click="update" variant="primary" wire:loading.attr="disabled">
                {{ __('Save changes') }}
            </flux:button>
        @endif
    </div>
</div>
