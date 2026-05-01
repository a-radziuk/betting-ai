@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'w-full rounded-md border border-[#3b4e75] bg-[#0f1b31] text-[#eaf0ff] placeholder-[#7f93bd] focus:border-[#5de2ff] focus:ring-[#5de2ff] shadow-sm']) }}>
