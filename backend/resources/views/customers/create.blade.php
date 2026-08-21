<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-xl font-semibold text-ink leading-tight">New Customer</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('customers.store') }}" class="bg-surface border border-line shadow-sm rounded-lg p-6 space-y-4">
                @csrf

                <div>
                    <x-input-label for="name" value="Name" required />
                    <x-text-input id="name" name="name" value="{{ old('name') }}" class="mt-1 block w-full" required autofocus />
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="phone" value="Phone" />
                    <x-text-input id="phone" name="phone" value="{{ old('phone') }}" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('phone')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="email" value="Email" />
                    <x-text-input id="email" type="email" name="email" value="{{ old('email') }}" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('email')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="address" value="Address" />
                    <textarea id="address" name="address" rows="2" class="mt-1 block w-full border-line rounded-md shadow-sm focus:border-accent focus:ring-accent">{{ old('address') }}</textarea>
                    <x-input-error :messages="$errors->get('address')" class="mt-1" />
                </div>

                @php
                    // A previously-selected category might itself be a
                    // subcategory (e.g. re-displaying after a validation
                    // error elsewhere on the form) — resolve its parent so
                    // the top-level select shows the right branch.
                    $displaySelectedId = old('customer_category_id');
                    $displayTopLevelId = $displaySelectedId;
                    if ($displaySelectedId) {
                        $displayCategory = \App\Models\CustomerCategory::find($displaySelectedId);
                        if ($displayCategory?->isSubcategory()) {
                            $displayTopLevelId = $displayCategory->parent_id;
                        }
                    }
                @endphp
                <div class="grid grid-cols-2 gap-4"
                     x-data="categoryPicker({
                        categories: {{ $categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'children' => $c->children->map(fn ($sc) => ['id' => $sc->id, 'name' => $sc->name])->values()])->values()->toJson() }},
                        initialTopLevelId: {{ $displayTopLevelId ? (int) $displayTopLevelId : 'null' }},
                        initialSelectedId: {{ $displaySelectedId ? (int) $displaySelectedId : 'null' }},
                     })">
                    <div>
                        <x-input-label for="customer_category_id" value="Category" />
                        <select id="customer_category_id" x-model.number="topLevelId" @change="onTopLevelChange()" class="mt-1 block w-full border-line rounded-md shadow-sm text-sm bg-surface text-ink focus:border-accent focus:ring-accent">
                            <option value="">No category</option>
                            <template x-for="c in categories" :key="c.id">
                                <option :value="c.id" x-text="c.name"></option>
                            </template>
                        </select>
                    </div>
                    <div x-show="subcategories.length > 0" x-cloak>
                        <x-input-label value="Subcategory" />
                        <select name="customer_category_id" x-model.number="selectedId" class="mt-1 block w-full border-line rounded-md shadow-sm text-sm bg-surface text-ink focus:border-accent focus:ring-accent">
                            <option :value="topLevelId">General (no subcategory)</option>
                            <template x-for="sc in subcategories" :key="sc.id">
                                <option :value="sc.id" x-text="sc.name"></option>
                            </template>
                        </select>
                    </div>
                    <template x-if="subcategories.length === 0">
                        <input type="hidden" name="customer_category_id" :value="topLevelId ?? ''">
                    </template>
                    <div class="col-span-2">
                        <x-input-error :messages="$errors->get('customer_category_id')" class="mt-1" />
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <a href="{{ route('customers.index') }}" class="px-4 py-2 text-sm text-ink-soft">Cancel</a>
                    <x-primary-button>Create Customer</x-primary-button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function categoryPicker({ categories, initialTopLevelId, initialSelectedId }) {
            return {
                categories,
                topLevelId: initialTopLevelId,
                selectedId: initialSelectedId,
                get subcategories() {
                    const top = this.categories.find(c => c.id === this.topLevelId);
                    return top ? top.children : [];
                },
                onTopLevelChange() {
                    // Switching the top-level category invalidates whatever
                    // subcategory was picked under the old one.
                    this.selectedId = this.topLevelId;
                },
            };
        }
    </script>
</x-app-layout>
