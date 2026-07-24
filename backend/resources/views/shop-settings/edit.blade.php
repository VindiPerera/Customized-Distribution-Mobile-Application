<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-xl font-semibold text-ink leading-tight">Shop Details</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-good-soft text-good text-sm px-4 py-2 rounded">{{ session('status') }}</div>
            @endif

            <p class="text-sm text-ink-soft">These details are printed on every sale receipt across the mobile app and admin portal.</p>

            <form method="POST" action="{{ route('shop-settings.update') }}" class="bg-surface border border-line shadow-sm rounded-lg p-6 space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <x-input-label for="name" value="Shop Name" required />
                    <x-text-input id="name" name="name" value="{{ old('name', $settings->name) }}" class="mt-1 block w-full" required autofocus />
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="address" value="Address" />
                    <textarea id="address" name="address" rows="2" class="mt-1 block w-full border-line rounded-md shadow-sm focus:border-accent focus:ring-accent">{{ old('address', $settings->address) }}</textarea>
                    <x-input-error :messages="$errors->get('address')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="phone" value="Phone" />
                    <x-text-input id="phone" name="phone" value="{{ old('phone', $settings->phone) }}" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('phone')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="tax_id" value="Tax / TIN Number" />
                    <x-text-input id="tax_id" name="tax_id" value="{{ old('tax_id', $settings->tax_id) }}" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('tax_id')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="footer_note" value="Receipt Footer Note" />
                    <x-text-input id="footer_note" name="footer_note" value="{{ old('footer_note', $settings->footer_note) }}" class="mt-1 block w-full" placeholder="e.g. Thank you! Come again." />
                    <x-input-error :messages="$errors->get('footer_note')" class="mt-1" />
                </div>

                <div class="flex justify-end gap-2">
                    <x-primary-button>Save</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
