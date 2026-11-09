<?php

use App\Models\Club;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination, WithFileUploads;

    // Properties
    public ?Club $editing = null;
    public ?Club $deleting = null;

    public $image = null;
    public ?string $oldImage = null;

    public ?string $name = null;
    public ?string $root_name = null;
    public ?string $phone = null;
    public ?string $address = null;

    public string $search = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->role === 'root', 403);
    }

    public function getClubsProperty(): LengthAwarePaginator
    {
        return Club::query()
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('name', 'like', "%{$this->search}%")
                        ->orWhere('root_name', 'like', "%{$this->search}%")
                        ->orWhere('phone', 'like', "%{$this->search}%")
                        ->orWhere('address', 'like', "%{$this->search}%");
                });
            })
            ->latest('id')
            ->paginate(10);
    }

    protected function rules(): array
    {
        return [
            'image' => ['nullable', 'image', 'max:2048'],
            'name' => ['required', 'string', 'max:255', 'not_regex:/\d/'],
            'root_name' => ['required', 'string', 'max:255', 'not_regex:/\d/'],
            'phone' => ['required', 'digits:11'],
            'address' => ['required', 'string', 'max:255'],
        ];
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[On('open-create-club')]
    public function openCreateClub(): void
    {
        $this->resetForm();

        $this->modal('club-modal')->show();
    }

    public function edit(Club $club): void
    {
        $this->resetForm();

        $this->editing = Club::query()->findOrFail($club->id);
        $this->oldImage = $this->editing->image;

        $this->name = $this->editing->name;
        $this->root_name = $this->editing->root_name;
        $this->phone = $this->editing->phone;
        $this->address = $this->editing->address;

        $this->modal('club-modal')->show();
    }

    public function store(): void
    {
        $validated = $this->validate();

        $clubData = $validated;
        unset($clubData['image']);

        if ($image = $this->saveImage()) {
            $clubData['image'] = $image;
        }

        Club::query()->create($clubData);

        $this->resetPage();

        $this->closeClubModal(__('Club saved.'));
    }

    public function update(): void
    {
        if (!$this->editing) {
            return;
        }

        $validated = $this->validate();

        $club = Club::query()->findOrFail($this->editing->id);

        $clubData = $validated;
        unset($clubData['image']);

        $newImage = $this->saveImage();

        if ($newImage) {
            $clubData['image'] = $newImage;
        }

        $oldImage = $club->image;

        $club->update($clubData);

        if ($newImage && $oldImage) {
            Storage::disk('public')->delete($oldImage);
        }

        $this->closeClubModal(__('Club updated.'));
    }

    public function confirmDelete(Club $club): void
    {
        $this->deleting = Club::query()->findOrFail($club->id);

        $this->modal('delete-club-modal')->show();
    }

    public function delete(): void
    {
        if (!$this->deleting) {
            return;
        }

        $club = Club::query()->findOrFail($this->deleting->id);

        $image = $club->image;

        $club->delete();

        if ($image) {
            Storage::disk('public')->delete($image);
        }

        $this->modal('delete-club-modal')->close();

        $this->deleting = null;

        $this->resetPage();

        Flux::toast(
            text: __('Club deleted.'),
            variant: 'success',
        );
    }

    // Helper Methods
    protected function saveImage(): ?string
    {
        if (!$this->image) {
            return null;
        }

        return $this->image->store('image', 'public');
    }

    protected function resetForm(): void
    {
        $this->reset([
            'editing',
            'image',
            'oldImage',
            'name',
            'root_name',
            'phone',
            'address',
        ]);

        $this->resetValidation();
    }

    protected function closeClubModal(string $message): void
    {
        $this->modal('club-modal')->close();

        $this->resetForm();

        Flux::toast(
            text: $message,
            variant: 'success',
        );
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
            :paginate="$this->clubs"
    >
        <flux:table.columns>
            <flux:table.column align="center">{{ __('Image') }}</flux:table.column>
            <flux:table.column align="center">{{ __('Club Name') }}</flux:table.column>
            <flux:table.column align="center">{{ __('Root Name') }}</flux:table.column>
            <flux:table.column align="center">{{ __('Phone') }}</flux:table.column>
            <flux:table.column align="center">{{ __('Address') }}</flux:table.column>
            <flux:table.column align="center">{{ __('Actions') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse($this->clubs as $club)
                <flux:table.row wire:key="club-{{ $club->id }}">
                    <flux:table.cell class="flex justify-center items-center">
                        @if($club->image)
                            <img
                                    src="{{ Storage::url($club->image) }}"
                                    class="w-15 rounded-xl object-cover"
                                    alt="{{ $club->name }}"
                            >
                        @else
                            <div class="w-15 h-15 rounded-xl bg-zinc-100 dark:bg-zinc-700 flex items-center justify-center">
                                -
                            </div>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell>{{ $club->name }}</flux:table.cell>
                    <flux:table.cell>{{ $club->root_name }}</flux:table.cell>
                    <flux:table.cell>{{ $club->phone }}</flux:table.cell>
                    <flux:table.cell>{{ $club->address }}</flux:table.cell>

                    <flux:table.cell>
                        <div class="flex gap-2 justify-center">
                            <flux:button
                                    wire:click="edit({{ $club->id }})"
                                    wire:loading.attr="disabled"
                            >
                                {{ __('Edit') }}
                            </flux:button>

                            <flux:button
                                    wire:click="confirmDelete({{ $club->id }})"
                                    variant="danger"
                                    wire:loading.attr="disabled"
                            >
                                {{ __('Delete') }}
                            </flux:button>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6">
                        {{ __('No clubs found.') }}
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <flux:modal
            name="club-modal"
            :flyout="true"
            position="left"
            :dismissible="false"
    >
        <form wire:submit="{{ $editing ? 'update' : 'store' }}" class="space-y-4">
            <flux:heading>
                {{ $editing ? __('Edit Club') : __('Create Club') }}
            </flux:heading>

            <flux:heading>{{ __('Club image') }}</flux:heading>

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

            <flux:separator />

            <flux:heading>{{ __('Club information') }}</flux:heading>

            <flux:input
                    wire:model="name"
                    label="{{ __('Club Name') }}"
                    required
            />

            <flux:input
                    wire:model="root_name"
                    label="{{ __('Root Name') }}"
                    required
            />

            <flux:input
                    wire:model="phone"
                    label="{{ __('Phone') }}"
            />

            <flux:input
                    wire:model="address"
                    label="{{ __('Address') }}"
            />

            <div class="flex justify-end">
                <flux:button
                        type="submit"
                        wire:loading.attr="disabled"
                >
                    {{ $editing ? __('Update') : __('Save') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="delete-club-modal" class="max-w-md">
        <div class="space-y-4">
            <flux:heading>{{ __('Delete Club') }}</flux:heading>

            <p>{{ __('Are you sure you want to delete this club?') }}</p>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button>{{ __('Cancel') }}</flux:button>
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