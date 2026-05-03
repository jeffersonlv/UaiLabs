<button {{ $attributes->merge(['type' => 'submit']) }} style="
    display: inline-flex;
    align-items: center;
    padding: 0.5rem 1.5rem;
    background-color: #1e293b;
    color: #ffffff;
    font-size: 0.875rem;
    font-weight: 600;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    letter-spacing: 0.05em;
    transition: background-color 0.15s;
" onmouseover="this.style.backgroundColor='#334155'" onmouseout="this.style.backgroundColor='#1e293b'">
    {{ $slot }}
</button>
