@props(['name', 'size' => null])
<span {{ $attributes->class(['avatar', 'avatar--c' . \App\Models\User::colorIndexFor($name), 'avatar--' . $size => $size]) }} aria-hidden="true">{{ \App\Models\User::initialsFor($name) }}</span>
