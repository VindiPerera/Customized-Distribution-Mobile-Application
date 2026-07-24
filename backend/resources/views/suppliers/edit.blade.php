<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-xl font-semibold text-ink leading-tight">Edit Supplier</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('suppliers.update', $supplier) }}" class="bg-surface border border-line shadow-sm rounded-lg p-6 space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <x-input-label for="name" value="Name" required />
                    <x-text-input id="name" name="name" value="{{ old('name', $supplier->name) }}" class="mt-1 block w-full" required autofocus />
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="contact_person" value="Contact Person" />
                    <x-text-input id="contact_person" name="contact_person" value="{{ old('contact_person', $supplier->contact_person) }}" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('contact_person')" class="mt-1" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="phone" value="Phone" />
                        <x-text-input id="phone" name="phone" value="{{ old('phone', $supplier->phone) }}" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('phone')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="email" value="Email" />
                        <x-text-input id="email" type="email" name="email" value="{{ old('email', $supplier->email) }}" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('email')" class="mt-1" />
                    </div>
                </div>

                <div>
                    <x-input-label for="address" value="Address" />
                    <textarea id="address" name="address" rows="2" class="mt-1 block w-full border-line rounded-md shadow-sm focus:border-accent focus:ring-accent">{{ old('address', $supplier->address) }}</textarea>
                    <x-input-error :messages="$errors->get('address')" class="mt-1" />
                </div>

                <div class="flex items-center">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $supplier->is_active) ? 'checked' : '' }} class="rounded border-line text-accent focus:ring-accent">
                    <label for="is_active" class="ml-2 text-sm text-ink-soft">Active</label>
                </div>

                <div class="flex justify-end gap-2">
                    <a href="{{ route('suppliers.index') }}" class="px-4 py-2 text-sm text-ink-soft">Cancel</a>
                    <x-primary-button>Save Changes</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
