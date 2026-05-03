@php
    $faces = [
        1 => ['😠', 'Muito insatisfeito', 'danger'],
        2 => ['😟', 'Insatisfeito',       'warning'],
        3 => ['😐', 'Neutro',             'secondary'],
        4 => ['🙂', 'Satisfeito',         'info'],
        5 => ['😄', 'Muito satisfeito',   'success'],
    ];
    $currentVal = old($fieldName);
@endphp

<div class="mb-1">
    @if(!empty($label))
        <div class="text-muted small mb-2">{{ $label }}</div>
    @endif
    <div class="d-flex gap-2">
        @foreach($faces as $val => [$emoji, $title, $color])
        <label class="face-label text-center" style="cursor:pointer" title="{{ $title }}">
            <input type="radio" name="{{ $fieldName }}" value="{{ $val }}"
                   class="d-none face-radio-{{ $fieldName }}"
                   {{ $currentVal == $val ? 'checked' : '' }}>
            <div class="face-btn border rounded-circle d-flex align-items-center justify-content-center
                        {{ $currentVal == $val ? 'border-' . $color . ' bg-' . $color . ' bg-opacity-10' : 'border-light' }}"
                 style="width:44px;height:44px;font-size:1.4rem;transition:transform .12s"
                 data-color="{{ $color }}"
                 data-field="{{ $fieldName }}">{{ $emoji }}</div>
        </label>
        @endforeach
        {{-- Limpar seleção --}}
        <button type="button" class="btn btn-link btn-sm text-muted p-0 align-self-center ms-1 face-clear"
                data-field="{{ $fieldName }}" title="Sem avaliação">✕</button>
    </div>
</div>

@once
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    function initFacePicker() {
        document.querySelectorAll('.face-label').forEach(function (label) {
            label.addEventListener('click', function () {
                var radio = label.querySelector('input[type=radio]');
                var field = radio.name;
                // Reset all in same group
                document.querySelectorAll('input.face-radio-' + field).forEach(function (r) {
                    var btn = r.nextElementSibling;
                    btn.classList.remove('border-danger','border-warning','border-secondary','border-info','border-success');
                    btn.classList.remove('bg-danger','bg-warning','bg-secondary','bg-info','bg-success','bg-opacity-10');
                    btn.classList.add('border-light');
                    btn.style.transform = 'scale(1)';
                });
                // Highlight selected
                var selBtn = radio.nextElementSibling;
                var color  = selBtn.dataset.color;
                selBtn.classList.remove('border-light');
                selBtn.classList.add('border-' + color, 'bg-' + color, 'bg-opacity-10');
                selBtn.style.transform = 'scale(1.2)';
            });
        });
        // Hover effect
        document.querySelectorAll('.face-btn').forEach(function (btn) {
            btn.addEventListener('mouseenter', function () {
                if (!btn.classList.contains('bg-opacity-10')) btn.style.transform = 'scale(1.15)';
            });
            btn.addEventListener('mouseleave', function () {
                if (!btn.classList.contains('bg-opacity-10')) btn.style.transform = 'scale(1)';
            });
        });
        // Clear buttons
        document.querySelectorAll('.face-clear').forEach(function (clearBtn) {
            clearBtn.addEventListener('click', function () {
                var field = clearBtn.dataset.field;
                document.querySelectorAll('input.face-radio-' + field).forEach(function (r) {
                    r.checked = false;
                    var btn = r.nextElementSibling;
                    btn.classList.remove('border-danger','border-warning','border-secondary','border-info','border-success');
                    btn.classList.remove('bg-danger','bg-warning','bg-secondary','bg-info','bg-success','bg-opacity-10');
                    btn.classList.add('border-light');
                    btn.style.transform = 'scale(1)';
                });
            });
        });
    }
    initFacePicker();
});
</script>
@endpush
@endonce
