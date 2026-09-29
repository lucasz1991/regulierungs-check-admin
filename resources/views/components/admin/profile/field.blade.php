@props(['label', 'value' => null])
<div class="rc-profile__field">
    <dt>{{ $label }}</dt>
    <dd @class(['rc-profile__missing' => blank($value)])>{{ filled($value) ? $value : 'Nicht angegeben' }}</dd>
</div>
