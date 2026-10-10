@props(['name', 'size' => 18])
<svg {{ $attributes->class(['icon']) }} width="{{ $size }}" height="{{ $size }}" aria-hidden="true" focusable="false"><use href="#i-{{ $name }}"/></svg>
