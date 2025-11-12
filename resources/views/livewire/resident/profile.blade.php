<div class="font-inter text-slate-700 dark:text-slate-200" data-resident-stack>
    <section class="p-6" data-resident-card data-variant="muted" data-resident-fade data-aos="fade-up">
        <h1 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ __('resident.profile.personal_information') }}</h1>
        <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ __('resident.profile.update_your_contact_data') }}</p>

        @if (session('status'))
            @php
                $statusType = session('status_type', 'success');
                $statusStyles = [
                    'success' => ['tone' => 'success', 'text' => 'text-emerald-700 dark:text-emerald-200'],
                    'error' => ['tone' => 'danger', 'text' => 'text-rose-600 dark:text-rose-200'],
                ][$statusType] ?? ['tone' => 'info', 'text' => 'text-slate-600 dark:text-slate-200'];
            @endphp
            <div class="mt-6" data-resident-card data-variant="muted">
                <div class="flex items-start gap-3 p-4 text-xs {{ $statusStyles['text'] }}">
                    <span data-resident-chip data-tone="{{ $statusStyles['tone'] }}">{{ __('resident.profile.status') }}</span>
                    <p>{{ session('status') }}</p>
                </div>
            </div>
        @endif

        <form wire:submit.prevent="save" class="mt-6 space-y-6">
            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="text-xs font-semibold text-slate-600 dark:text-slate-300">{{ __('resident.profile.full_name') }}</label>
                    <input type="text" wire:model.defer="name" data-resident-control class="mt-2">
                    @error('name') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600 dark:text-slate-300">{{ __('resident.profile.email') }}</label>
                    <input type="email" wire:model.defer="email" data-resident-control class="mt-2">
                    @error('email') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                    @if ($pendingEmail)
                        <p class="mt-2 text-xs text-emerald-600 dark:text-emerald-300">
                            {{ __('resident.profile.new_email_awaits_verification') }} <a href="{{ route('resident.verification.notice', ['context' => 'change']) }}" class="font-semibold underline underline-offset-2">{{ __('resident.profile.enter_otp_code') }}</a> {{ __('resident.profile.to_complete_the_change') }}
                        </p>
                    @endif
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600 dark:text-slate-300">{{ __('resident.profile.phone_number') }}</label>
                    <input type="text" wire:model.defer="phone" data-resident-control class="mt-2">
                    @error('phone') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600 dark:text-slate-300">{{ __('resident.profile.address') }}</label>
                    <input type="text" wire:model.defer="alamat" data-resident-control class="mt-2">
                    @error('alamat') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="text-xs font-semibold text-slate-600 dark:text-slate-300">{{ __('resident.profile.current_password') }}</label>
                    <input type="password" wire:model.defer="currentPassword" data-resident-control class="mt-2">
                    <p class="mt-1 text-[0.68rem] text-slate-400 dark:text-slate-500">{{ __('resident.profile.required_when_changing_password') }}</p>
                    @error('currentPassword') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600 dark:text-slate-300">{{ __('resident.profile.new_password') }}</label>
                    <input id="profile_new_password" type="password" wire:model.defer="password" data-resident-control class="mt-2">
                    <div class="password-strength mt-3" data-password-strength="profile_new_password">
                        <div class="password-strength__track">
                            <div class="password-strength__bar" data-strength-bar></div>
                        </div>
                        <p class="password-strength__label" data-strength-text>{{ __('resident.profile.password_strength_prompt') }}</p>
                    </div>
                    @error('password') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600 dark:text-slate-300">{{ __('resident.profile.confirm_password') }}</label>
                    <input type="password" wire:model.defer="password_confirmation" data-resident-control class="mt-2">
                </div>
            </div>

            <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                <div class="flex items-center gap-5">
                    <div class="h-20 w-20 overflow-hidden rounded-full border border-white/70 bg-slate-100 shadow-inner dark:border-slate-700">
                        @if ($profilePhoto)
                            <img src="{{ $profilePhoto->temporaryUrl() }}" class="h-full w-full object-cover" alt="{{ __('resident.profile.preview') }}">
                        @elseif (auth()->user()->profile_photo_url)
                            <img src="{{ auth()->user()->profile_photo_url }}" class="h-full w-full object-cover" alt="{{ __('resident.directory.avatar') }}">
                        @else
                            <div class="flex h-full w-full items-center justify-center text-xs font-medium text-slate-400">{{ __('resident.profile.no_photo_yet') }}</div>
                        @endif
                    </div>
                    <div class="text-xs text-slate-500 dark:text-slate-400">
                        <label class="mb-2 inline-block font-semibold text-slate-600 dark:text-slate-200">{{ __('resident.profile.profile_photo') }}</label>
                        <input type="file" wire:model="profilePhoto" accept="image/*" class="text-xs text-slate-600 dark:text-slate-300">
                        @error('profilePhoto') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                        <button type="button" wire:click="toggleRemovePhoto" class="mt-3 inline-flex items-center text-xs font-semibold text-rose-600 transition-colors duration-200 hover:text-rose-700">{{ $removePhoto ? __('resident.profile.cancel') : __('resident.profile.remove_photo') }}</button>
                    </div>
                </div>
            </div>

            <button type="submit" class="inline-flex w-full items-center justify-center rounded-full bg-[#22C55E] px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-[#22C55E]/30 transition-colors duration-200 hover:bg-[#16A34A] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#22C55E]/30 focus-visible:ring-offset-2 focus-visible:ring-offset-white">
                {{ __('resident.profile.save_changes') }}
            </button>
        </form>
    </section>

    <section class="mt-6" data-resident-fade data-aos="fade-up">
        @livewire('resident.experience-preferences')
    </section>

    <livewire:resident.telegram-connector />
</div>