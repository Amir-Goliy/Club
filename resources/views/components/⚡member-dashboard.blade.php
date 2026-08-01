<?php

use App\Models\Member;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public Member $member;

    public bool $edit = false;

    public $image;
    public string $phone;

    public function mount(): void
    {
        $this->member = auth()->user()->member;

        $this->phone = $this->member->phone;
    }

    protected function rules(): array
    {
        return [
            'phone' => 'nullable|digits:11',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ];
    }

    public function editing(): void
    {
        $this->edit = true;
    }

    public function cancel(): void
    {
        $this->edit = false;

        $this->resetValidation();
    }

    public function update(): void
    {
        $validated = $this->validate();

        if ($this->member->image)
            $validated['image'] = $this->member->image;

        if ($this->image) {
            if ($this->member->image)
                Storage::disk('public')->delete($this->member->image);

            $validated['image'] = $this->image->store('image', 'public');
        }

        $this->member->update($validated);

        $this->member->refresh();

        $this->edit = false;

        Flux::toast(text: __('Profile updated.'), heading: __('Saved.'), variant: 'success');
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

            @if($edit)
                <flux:input
                    type="file"
                    accept=".png,.jpg,.jpeg"
                    wire:model.live="image"
                    label="{{ __('Profile image') }}"
                />
            @endif

            <h1 class="text-2xl font-bold">
                {{ $member->first_name }}
                {{ $member->last_name }}
            </h1>
            <p>
                {{ __('Club member') }}
                {{ $member->club->name }}
            </p>
        </div>
    </flux:card>

    <flux:card>
        <flux:heading class="mb-6">
            {{ __('Personal information') }}
        </flux:heading>

        <div class="grid sm:grid-cols-2 gap-6">
            <div>
                <span class="text-sm text-zinc-700 dark:text-zinc-300">
                    {{ __('National code') }}
                </span>
                <p>
                    {{ $member->national_code }}
                </p>
            </div>

            <div>
                <span class="text-sm text-zinc-700 dark:text-zinc-300">
                    {{ __('Joined at') }}
                </span>
                <p>
                    {{ $member->created_at->format('Y/m/d') }}
                </p>
            </div>

            <div>
                <span class="text-sm text-zinc-700 dark:text-zinc-300">
                    {{ __('Phone') }}
                </span>
                @if($edit)
                    <flux:input
                        wire:model.live="phone"
                    />
                @else
                    <p>
                        {{ $member->phone }}
                    </p>
                @endif
            </div>

            <div>
                <span class="text-sm text-zinc-700 dark:text-zinc-300">
                    {{ __('Birth date') }}
                </span>
                <p>
                    {{ $member->birth_date }}
                </p>
            </div>
        </div>
    </flux:card>

    @if(!$edit)
        <div class="flex">
            <flux:button
                wire:click="editing"
                variant="primary"
            >
                {{ __('Edit profile') }}
            </flux:button>
        </div>
    @else
        <div class="flex gap-3">
            <flux:button
                wire:click="cancel"
                variant="ghost"
            >
                {{ __('Cancel') }}
            </flux:button>

            <flux:button
                wire:click="update"
                variant="primary"
            >
                {{ __('Save changes') }}
            </flux:button>
        </div>
    @endif
</div>
