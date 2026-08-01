<?php

use App\Models\Member;
use Illuminate\Pagination\LengthAwarePaginator;
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

    public function getMembersProperty(): LengthAwarePaginator
    {
        return Member::query()
            ->where('club_id', auth()->user()->club_id)
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('first_name', 'like', "%{$this->search}%")
                        ->orWhere('last_name', 'like', "%{$this->search}%")
                        ->orWhere('national_code', 'like', "%{$this->search}%")
                        ->orWhere('phone', 'like', "%{$this->search}%");
                });
            })
            ->latest()
            ->paginate(10);
    }

    protected function rules(): array
    {
        return [
            'image' => 'nullable|max:2048',
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
        ]);

        $this->clearValidation();
    }

    #[On('open-create-member')]
    public function openCreateUser(): void
    {
        $this->resetForm();

        $this->modal('member-modal')->show();
    }

    public function store(): void
    {
        $validated = $this->validate();

        $validated['club_id'] = auth()->user()->club->id;

        if ($this->image) {
            $validated['image'] = $this->image->store('image', 'public');
        }

        Member::query()->create($validated);

        $this->modal('member-modal')->close();

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

        $this->modal('member-modal')->show();
    }

    public function update(): void
    {
        $validated = $this->validate();

        if ($this->image && ! is_string($this->image)) {

            if (
                $this->editing->image &&
                Storage::disk('public')->exists($this->editing->image)
            ) {
                Storage::disk('public')->delete($this->editing->image);
            }

            $validated['image'] = $this->image->store('image', 'public');
        }

        $this->editing->update($validated);

        $this->modal('member-modal')->close();

        $this->resetForm();
    }

    public function delete(Member $member): void
    {
        Storage::disk('public')->delete($member->image);
        $member->delete();
    }
};
?>

<div>
    <div class="flex justify-center mb-6">
        <div class="w-full max-w-50">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="جستجوی اعضا..."
            />
        </div>
    </div>

    <div>
        <flux:table class="text-center" :paginate="$this->members">
            <flux:table.columns>
                <flux:table.column align="center">{{ __('Image') }}</flux:table.column>

                <flux:table.column align="center">{{ __('First Name') }}</flux:table.column>

                <flux:table.column align="center">{{ __('Last Name') }}</flux:table.column>

                <flux:table.column align="center">{{ __('National Code') }}</flux:table.column>

                <flux:table.column align="center">{{ __('Birth date') }}</flux:table.column>

                <flux:table.column align="center">{{ __('Phone') }}</flux:table.column>

                <flux:table.column align="center">{{ __('Edit') }}</flux:table.column>

                <flux:table.column align="center">{{ __('Delete') }}</flux:table.column>
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

                        <flux:table.cell>{{ $member->first_name }}</flux:table.cell>

                        <flux:table.cell>{{ $member->last_name }}</flux:table.cell>

                        <flux:table.cell>{{ $member->national_code }}</flux:table.cell>

                        <flux:table.cell>{{ $member->birth_date }}</flux:table.cell>

                        <flux:table.cell>{{ $member->phone }}</flux:table.cell>

                        <flux:table.cell>
                            <flux:button wire:click="edit({{ $member->id }})">
                                {{ __('Edit') }}
                            </flux:button>
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:button wire:click="delete({{ $member->id }})" variant="primary">
                                {{ __('Delete') }}
                            </flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal :flyout="true" position="left" :dismissible="false" name="member-modal">
        <div class="space-y-4">
            <flux:heading>
                {{ $editing ? __('Edit Member') : __('Create Member') }}
            </flux:heading>

            <flux:input type="file" wire:model.live="image" label="{{ __('Image') }}"/>

            @if ($image)
                <div class="flex justify-center items-center">
                    <img
                        src="{{ is_string($image) ? Storage::url($image) : $image->temporaryUrl() }}"
                        class="w-20 sm:w-80 rounded-xl object-cover"
                        alt=""
                    >
                </div>
            @endif

            <flux:input wire:model.live="first_name" label="{{ __('First Name') }}"/>

            <flux:input wire:model.live="last_name" label="{{ __('Last Name') }}"/>

            <flux:input wire:model.live="national_code" label="{{ __('National Code') }}"/>

            <flux:input mask="****-**-**" wire:model.live="birth_date" label="{{ __('Birth date') }}"/>

            <flux:input wire:model.live="phone" label="{{ __('Phone') }}"/>

            <div class="flex justify-end">
                <flux:button wire:click="{{ $editing ? 'update' : 'store' }}">
                    {{ __('Save') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
