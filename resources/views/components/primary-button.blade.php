<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 rounded-md font-semibold text-xs text-[#041025] uppercase tracking-widest border border-transparent bg-gradient-to-r from-[#5de2ff] to-[#8a7bff] hover:brightness-110 focus:outline-none focus:ring-2 focus:ring-[#5de2ff] focus:ring-offset-0 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
