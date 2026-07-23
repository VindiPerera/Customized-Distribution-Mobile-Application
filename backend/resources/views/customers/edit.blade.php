<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-xl font-semibold text-ink leading-tight">Edit Customer</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('customers.update', $customer) }}" class="bg-surface border border-line shadow-sm rounded-lg p-6 space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <x-input-label for="name" value="Name" />
                    <x-text-input id="name" name="name" value="{{ old('name', $customer->name) }}" class="mt-1 block w-full" required autofocus />
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="phone" value="Phone" />
                    <x-text-input id="phone" name="phone" value="{{ old('phone', $customer->phone) }}" class="mt-1 block w-full" />
                </div>

                <div>
                    <x-input-label for="email" value="Email" />
                    <x-text-input id="email" type="email" name="email" value="{{ old('email', $customer->email) }}" class="mt-1 block w-full" />
                </div>

                <div>
                    <x-input-label for="address" value="Address" />
                    <textarea id="address" name="address" rows="2" class="mt-1 block w-full border-line rounded-md shadow-sm focus:border-accent focus:ring-accent">{{ old('address', $customer->address) }}</textarea>
                </div>

                <div>
                    <x-input-label for="credit_limit" value="Credit Limit (Rs.)" />
                    <x-text-input id="credit_limit" type="number" step="0.01" min="0" name="credit_limit" value="{{ old('credit_limit', $customer->credit_limit) }}" class="mt-1 block w-full" required />
                    <x-input-error :messages="$errors->get('credit_limit')" class="mt-1" />
                </div>

                <div class="flex items-center">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $customer->is_active) ? 'checked' : '' }} class="rounded border-line text-accent focus:ring-accent">
                    <label for="is_active" class="ml-2 text-sm text-ink-soft">Active</label>
                </div>

                <div class="flex justify-end gap-2">
                    <a href="{{ route('customers.show', $customer) }}" class="px-4 py-2 text-sm text-ink-soft">Cancel</a>
                    <x-primary-button>Save Changes</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
