<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center px-4 py-2 bg-[#152540] border border-[#3b4e75] rounded-md font-semibold text-xs text-[#dce7ff] uppercase tracking-widest shadow-sm hover:bg-[#1a2d4d] focus:outline-none focus:ring-2 focus:ring-[#5de2ff] focus:ring-offset-0 disabled:opacity-25 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
