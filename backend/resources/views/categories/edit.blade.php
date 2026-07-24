<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-xl font-semibold text-ink leading-tight">Edit Category</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('categories.update', $category) }}" class="bg-surface border border-line shadow-sm rounded-lg p-6 space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <x-input-label for="name" value="Name" required />
                    <x-text-input id="name" name="name" value="{{ old('name', $category->name) }}" class="mt-1 block w-full" required autofocus />
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="description" value="Description" />
                    <textarea id="description" name="description" rows="3" class="mt-1 block w-full border-line rounded-md shadow-sm focus:border-accent focus:ring-accent">{{ old('description', $category->description) }}</textarea>
                    <x-input-error :messages="$errors->get('description')" class="mt-1" />
                </div>

                <div class="flex items-center">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $category->is_active) ? 'checked' : '' }} class="rounded border-line text-accent focus:ring-accent">
                    <label for="is_active" class="ml-2 text-sm text-ink-soft">Active</label>
                </div>

                <div class="flex justify-end gap-2">
                    <a href="{{ route('categories.index') }}" class="px-4 py-2 text-sm text-ink-soft">Cancel</a>
                    <x-primary-button>Save Changes</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
