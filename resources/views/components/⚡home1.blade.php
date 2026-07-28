<?php

use App\Models\Member;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public ?Member $editing = null;

    public $first_name;
    public $last_name;
    public $father_name;
    public $birth_date;
    public $national_code;
    public $phone;
    public $image;

    public function getMembersProperty(): LengthAwarePaginator
    {
        return Member::query()->orderByDesc('id')->paginate(7);
    }

    protected function rules(): array
    {
        return [
            'first_name' => 'required|string|not_regex:/\d/',
            'last_name' => 'required|string|not_regex:/\d/',
            'father_name' => 'required|string|not_regex:/\d/',
            'birth_date' => 'required|date',
            'national_code' => 'required|digits:10|unique:members,national_code,' . ($this->editing?->id),
            'phone' => 'required|digits:11',
        ];
    }

    protected function resetForm(): void
    {
        $this->reset([
            'editing',
            'first_name',
            'last_name',
            'father_name',
            'birth_date',
            'national_code',
            'phone',
            'image',
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

        Member::query()->create($validated);

        $this->modal('member-modal')->close();

        $this->resetForm();
    }

    public function edit(Member $member): void
    {
        $this->editing = $member;

        $this->fill($member->only([
            'first_name',
            'last_name',
            'father_name',
            'birth_date',
            'national_code',
            'phone',
        ]));

        $this->modal('member-modal')->show();
    }

    public function update(): void
    {
        $validated = $this->validate();

        $this->editing->update($validated);

        $this->modal('member-modal')->close();

        $this->resetForm();
    }

    public function delete(Member $member): void
    {
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

            <flux:table.column align="center">{{ __('Father Name') }}</flux:table.column>

            <flux:table.column align="center">{{ __('Birth Date') }}</flux:table.column>

            <flux:table.column align="center">{{ __('National Code') }}</flux:table.column>

            <flux:table.column align="center">{{ __('Phone') }}</flux:table.column>

            <flux:table.column align="center">{{ __('Edit') }}</flux:table.column>

            <flux:table.column align="center">{{ __('Delete') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach($this->members as $member)
                <flux:table.row>
                    <flux:table.cell>
                        <img class="rounded-xl w-20" src="{{ asset('img/'.$member->image) }}" alt="">
                    </flux:table.cell>

                    <flux:table.cell>{{ $member->first_name }}</flux:table.cell>

                    <flux:table.cell>{{ $member->last_name }}</flux:table.cell>

                    <flux:table.cell>{{ $member->father_name }}</flux:table.cell>

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

    <flux:modal name="member-modal">
        <div class="space-y-4">
            <flux:heading>
                {{ $editing ? __('Edit') : __('Create') }}
            </flux:heading>

            <flux:input type="file" wire:model="image" label="{{ __('Image') }}"/>

            <flux:input wire:model="first_name" label="{{ __('First Name') }}"/>

            <flux:input wire:model="last_name" label="{{ __('Last Name') }}"/>

            <flux:input wire:model="father_name" label="{{ __('Father Name') }}"/>

            <flux:input type="date" max="1399-12-31" wire:model="birth_date" label="{{ __('Birth Date') }}"/>

            <flux:input wire:model="national_code" label="{{ __('National Code') }}"/>

            <flux:input wire:model="phone" label="{{ __('Phone') }}"/>

            <div class="flex justify-end">
                <flux:button wire:click="{{ $editing ? 'update' : 'store' }}">
                    {{ $editing ? __('Save') : __('Create') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
