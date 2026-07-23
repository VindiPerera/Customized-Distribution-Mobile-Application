@props(['messages'])

@if ($messages)
    <ul {{ $attributes->merge(['class' => 'text-[0.8rem] text-critical space-y-0.5']) }}>
        @foreach ((array) $messages as $message)
            <li>{{ $message }}</li>
        @endforeach
    </ul>
@endif
