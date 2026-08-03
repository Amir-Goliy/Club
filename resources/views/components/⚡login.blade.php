<?php

use App\Models\Member;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.auth')]
class extends Component {
    public int $step = 1;

    public string $national_code = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $remember = false;

    public ?Member $member = null;

    public function mount(): void
    {
        if (auth()->check()) {
            $this->redirectByRole();
        }
    }

    protected function rules(): array
    {
        return match ($this->step) {
            1 => [
                'national_code' => ['required', 'digits:10'],
            ],

            2 => [
                'password' => ['required', 'string'],
            ],

            3 => [
                'password' => [
                    'required',
                    'string',
                    'min:8',
                    'max:255',
                    'confirmed',
                ],
            ],

            default => [],
        };
    }

    public function checkNationalCode(): void
    {
        $this->validate();

        $this->ensureIsNotRateLimited();

        $user = User::query()
            ->where('national_code', $this->national_code)
            ->first();

        if ($user) {
            $this->step = 2;
            return;
        }

        $this->member = Member::query()
            ->where('national_code', $this->national_code)
            ->whereNull('user_id')
            ->first();

        if ($this->member) {
            $this->step = 3;
            return;
        }

        RateLimiter::hit($this->throttleKey(), 60);

        $this->addError('national_code', __('National code not found.'));
    }

    public function login(): void
    {
        $this->validate();

        $this->ensureIsNotRateLimited();

        if (!Auth::attempt([
            'national_code' => $this->national_code,
            'password' => $this->password,
        ], $this->remember)) {

            RateLimiter::hit($this->throttleKey(), 60);

            $this->addError('password', __('Invalid password.'));

            return;
        }

        RateLimiter::clear($this->throttleKey());

        session()->regenerate();

        $this->redirectByRole();
    }

    public function setPassword(): void
    {
        $this->validate();

        if (!$this->member || $this->member->user_id) {
            $this->addError('national_code', __('This account has already been activated.'));
            $this->step = 1;
            return;
        }

        $user = User::create([
            'club_id' => $this->member->club_id,
            'name' => trim($this->member->first_name . ' ' . $this->member->last_name),
            'national_code' => $this->member->national_code,
            'password' => Hash::make($this->password),
            'role' => 'user',
        ]);

        $this->member->update([
            'user_id' => $user->id,
        ]);

        Auth::login($user);
        session()->regenerate();

        Flux::toast(text: __('Your account has been created successfully.'), variant: 'success');

        $this->redirectByRole();
    }

    public function backToStart(): void
    {
        $this->reset([
            'step',
            'member',
            'password',
            'password_confirmation',
            'remember',
        ]);

        $this->step = 1;

        $this->resetValidation();
    }

    protected function redirectByRole(): void
    {
        $this->redirect(
            auth()->user()->role === 'admin'
                ? route('admin.dashboard')
                : route('member.dashboard'),
            navigate: true
        );
    }

    protected function ensureIsNotRateLimited(): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey());

        $this->addError('national_code', __('Too many attempts. Please try again in :seconds seconds.', [
            'seconds' => $seconds,
        ]));
    }

    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->national_code) . '|' . request()->ip());
    }
};

?>

<div class="flex flex-col gap-6">
    <x-auth-header
        :title="__('Log in to your account')"
        :description="__('Enter your national code and password below to log in')"
    />

    <x-auth-session-status
        class="text-center"
        :status="session('status')"
    />

    {{-- Step 1: Check National Code --}}
    @if ($step === 1)

        <form wire:submit="checkNationalCode" class="flex flex-col gap-6">

            <flux:input
                wire:model="national_code"
                :label="__('National code')"
                required
                autofocus
                type="number"
                :placeholder="__('National code')"
            />

            <flux:button
                type="submit"
                variant="primary"
                class="w-full"
                wire:loading.attr="disabled"
                wire:target="checkNationalCode"
            >
                {{ __('Continue') }}
            </flux:button>

        </form>

        {{-- Step 2: Login Existing User --}}
    @elseif ($step === 2)

        <form wire:submit="login" class="flex flex-col gap-6">

            <div class="flex items-end gap-2">
                <div class="flex-1">
                    <flux:input
                        :value="$national_code"
                        :label="__('National code')"
                        disabled
                    />
                </div>

                <flux:button
                    type="button"
                    variant="subtle"
                    wire:click="backToStart"
                    class="shrink-0 mb-0.5"
                >
                    {{ __('Change') }}
                </flux:button>
            </div>

            <flux:input
                wire:model="password"
                :label="__('Password')"
                type="password"
                autocomplete="current-password"
                :placeholder="__('Password')"
                viewable
            />

            <flux:checkbox
                wire:model="remember"
                :label="__('Remember me')"
            />

            <flux:button
                type="submit"
                variant="primary"
                class="w-full"
                wire:loading.attr="disabled"
                wire:target="login"
            >
                {{ __('Log in') }}
            </flux:button>

        </form>

    {{-- Step 3: First Login - Set Password --}}
    @elseif ($step === 3)

        <form wire:submit="setPassword" class="flex flex-col gap-6">

            <div class="flex items-center gap-2">
                <flux:input
                    :value="$national_code"
                    :label="__('National code')"
                    disabled
                    class="flex-1"
                />

                <flux:button
                    type="button"
                    variant="subtle"
                    wire:click="backToStart"
                    class="mt-6"
                >
                    {{ __('Change') }}
                </flux:button>
            </div>

            @if($member)
                <p class="text-sm text-zinc-500">
                    {{ __('Welcome, :name. Please set a password for your new account.', [
                        'name' => trim($member->first_name . ' ' . $member->last_name),
                    ]) }}
                </p>
            @endif

            <flux:input
                wire:model="password"
                :label="__('Set password')"
                type="password"
                autocomplete="new-password"
                :placeholder="__('Password')"
                viewable
            />

            <flux:input
                wire:model="password_confirmation"
                :label="__('Confirm password')"
                type="password"
                autocomplete="new-password"
                :placeholder="__('Confirm password')"
                viewable
            />

            <flux:button
                type="submit"
                variant="primary"
                class="w-full"
                wire:loading.attr="disabled"
                wire:target="setPassword"
            >
                {{ __('Create account') }}
            </flux:button>

        </form>
    @endif
</div>
