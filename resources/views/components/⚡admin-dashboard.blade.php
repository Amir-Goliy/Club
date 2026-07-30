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
    public string $birth_date;
    public string $national_code;
    public string $phone;

    public function getMembersProperty(): LengthAwarePaginator
    {
        return Member::query()->where('club_id', auth()->user()->club->id)->orderByDesc('id')->paginate(10);
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
            'birth_date',
            'national_code',
            'phone',
        ]);
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

        $validated['image'] = $this->image->store('image', 'public');

        Member::query()->create($validated);

        $this->modal('member-modal')->close();

        $this->resetForm();
    }

    public function edit(Member $member): void
    {
        $this->editing = $member;

        $this->fill($member->only([
            'image',
            'first_name',
            'last_name',
            'birth_date',
            'national_code',
            'phone',
        ]));

        $this->modal('member-modal')->show();
    }

    public function update(): void
    {
        $validated = $this->validate();

        if ($this->image && !is_string($this->image)) {
            Storage::disk('public')->delete($this->editing->image);

            $validated['image'] = $this->image->store('image', 'public');
        } else {
            $validated['image'] = $this->editing->image;
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
    <flux:table class="text-center" :paginate="$this->members">
        <flux:table.columns>
            <flux:table.column align="center">{{ __('Image') }}</flux:table.column>

            <flux:table.column align="center">{{ __('First Name') }}</flux:table.column>

            <flux:table.column align="center">{{ __('Last Name') }}</flux:table.column>

            <flux:table.column align="center">{{ __('Birth Date') }}</flux:table.column>

            <flux:table.column align="center">{{ __('National Code') }}</flux:table.column>

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
                            class="w-30 rounded-xl object-cover"
                            alt=""
                        >
                    </flux:table.cell>

                    <flux:table.cell>{{ $member->first_name }}</flux:table.cell>

                    <flux:table.cell>{{ $member->last_name }}</flux:table.cell>

                    <flux:table.cell>{{ $member->birth_date }}</flux:table.cell>

                    <flux:table.cell>{{ $member->national_code }}</flux:table.cell>

                    <flux:table.cell>{{ $member->phone }}</flux:table.cell>

                    <flux:table.cell>
                        <flux:button wire:click="edit({{ $member->id }})" variant="primary" color="yellow"
                        >{{ __('Edit') }}
                        </flux:button>
                    </flux:table.cell>

                    <flux:table.cell>
                        <flux:button wire:click="delete({{ $member->id }})" variant="danger">
                            {{ __('Delete') }}
                        </flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <flux:modal :dismissible="false" name="member-modal">
        <div class="space-y-4">
            <flux:heading>
                {{ $editing ? __('Edit') : __('Create') }}
            </flux:heading>

            <flux:input type="file" wire:model.live="image" label="{{ __('Image') }}"/>

            @if ($image)
                <div class="flex justify-center items-center">
                    <img
                        src="{{ is_string($image) ? Storage::url($image) : $image->temporaryUrl() }}"
                        class="w-80 rounded-xl object-cover"
                        alt=""
                    >
                </div>
            @endif

            <flux:input wire:model.live="first_name" label="{{ __('First Name') }}"/>

            <flux:input wire:model.live="last_name" label="{{ __('Last Name') }}"/>

            <flux:input type="date" max="1399-12-31" wire:model.live="birth_date" label="{{ __('Birth Date') }}"/>

            <flux:input wire:model.live="national_code" label="{{ __('National Code') }}"/>

            <flux:input wire:model.live="phone" label="{{ __('Phone') }}"/>

            <div class="flex justify-end">
                <flux:button wire:click="{{ $editing ? 'update' : 'store' }}">
                    {{ $editing ? __('Save') : __('Create') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
