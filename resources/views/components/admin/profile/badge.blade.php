@props(['tone' => 'neutral'])
<span {{ $attributes->class(['rc-profile__badge', 'rc-profile__badge--'.(in_array($tone, ['neutral', 'success', 'danger', 'warning'], true) ? $tone : 'neutral')]) }}>{{ $slot }}</span>
