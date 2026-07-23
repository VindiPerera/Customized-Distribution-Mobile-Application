<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-xl font-semibold text-ink leading-tight">New Staff Account</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('users.store') }}" class="bg-surface border border-line shadow-sm rounded-lg p-6 space-y-4">
                @csrf

                <div>
                    <x-input-label for="name" value="Name" />
                    <x-text-input id="name" name="name" value="{{ old('name') }}" class="mt-1 block w-full" required autofocus />
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="email" value="Email" />
                    <x-text-input id="email" type="email" name="email" value="{{ old('email') }}" class="mt-1 block w-full" required />
                    <x-input-error :messages="$errors->get('email')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="password" value="Password" />
                    <x-text-input id="password" type="password" name="password" class="mt-1 block w-full" required />
                    <x-input-error :messages="$errors->get('password')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="role" value="Role" />
                    <select id="role" name="role" class="mt-1 block w-full border-line rounded-md shadow-sm text-sm bg-surface text-ink focus:border-accent focus:ring-accent">
                        <option value="staff">Staff</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>

                <div class="flex justify-end gap-2">
                    <a href="{{ route('users.index') }}" class="px-4 py-2 text-sm text-ink-soft hover:text-ink">Cancel</a>
                    <x-primary-button>Create Account</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
