<?php

use App\Models\Member;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::auth')]
class extends Component {

    public string $national_code;
    public string $password;
    public string $password_confirmation;

    protected function rules(): array
    {
        return [
            'national_code' => 'required|digits:10',
            'password' => 'required|min:8|confirmed'
        ];
    }

    public function activate(): void
    {
        $validated = $this->validate();

        $member = Member::query()->where('national_code', $this->national_code)->first();

        if (!$member) {
            $this->addError(
                'national_code',
                'عضوی با این کد ملی پیدا نشد'
            );

            return;
        }

        if ($member->user_id) {
            $this->addError(
                'national_code',
                'این حساب قبلاً فعال شده است'
            );

            return;
        }

        $user = User::create([
            'club_id' => $member->club->id,
            'name' => $member->first_name . ' ' . $member->last_name,
            'national_code' => $member->national_code,
            'password' => Hash::make($this->password),
            'role' => 'user',
        ]);

        $member->update([
            'user_id' => $user->id,
        ]);

        Auth::login($user);

        $this->redirectRoute('member.dashboard');
    }

};
?>

<div class="flex flex-col gap-6">
    <x-auth-header :title="__('Create an account')"
                   :description="__('Enter your email and password below to log in')"/>

    <!-- National Code -->
    <flux:input
        wire:model="national_code"
        :label="__('National code')"
        required
        autofocus
        autocomplete="national_code"
        :placeholder="__('National code')"
    />

    <!-- Password -->
    <flux:input
        wire:model="password"
        :label="__('Password')"
        type="password"
        required
        autocomplete="new-password"
        :placeholder="__('Password')"
        passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
        viewable
    />

    <!-- Confirm Password -->
    <flux:input
        wire:model="password_confirmation"
        :label="__('Confirm password')"
        type="password"
        required
        autocomplete="new-password"
        :placeholder="__('Confirm password')"
        passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
        viewable
    />

    <div class="flex items-center justify-end">
        <flux:button wire:click="activate" variant="primary" type="submit" class="w-full" data-test="login-button">
            {{ __('Log in') }}
        </flux:button>
    </div>
</div>
