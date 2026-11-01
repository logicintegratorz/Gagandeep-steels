@props(['name', 'size' => 20])
<svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
@switch($name)
@case('mail')<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 6 9 7 9-7"/>@break
@case('phone')<path d="M7 3H4a1 1 0 00-1 1c0 9.4 7.6 17 17 17a1 1 0 001-1v-3l-5-2-2 2a15 15 0 01-7-7l2-2z"/>@break
@case('chat')<path d="M21 11.5a9 9 0 01-13.5 7.8L3 21l1.7-4.5A9 9 0 1121 11.5z"/><path d="M8 9h8M8 13h5"/>@break
@case('document')<path d="M14 3H5v18h14V8zM14 3v5h5M8 12h8M8 16h8"/>@break
@case('bell')<path d="M18 8a6 6 0 00-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>@break
@case('users')<circle cx="9" cy="7" r="3"/><path d="M3 21v-3a6 6 0 0112 0v3M16 4a3 3 0 010 6M17 14a5 5 0 014 5v2"/>@break
@case('lock')<rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V6a4 4 0 018 0v4M12 14v3"/>@break
@case('truck')<path d="M3 5h11v12H3zM14 9h4l3 4v4h-7"/><circle cx="7" cy="18" r="2"/><circle cx="18" cy="18" r="2"/>@break
@case('route')<circle cx="6" cy="5" r="2"/><circle cx="18" cy="19" r="2"/><path d="M6 7v7a4 4 0 004 4h6M8 5h6a4 4 0 010 8h-4"/>@break
@case('plus')<path d="M12 5v14M5 12h14"/>@break
@case('search')<circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 4 4"/>@break
@case('eye')<path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6z"/><circle cx="12" cy="12" r="2.5"/>@break
@case('download')<path d="M12 3v12m-5-5 5 5 5-5M4 16v5h16v-5"/>@break
@case('edit')<path d="m15 5 4 4M4 20l5-1L21 7a2.8 2.8 0 00-4-4L5 15z"/>@break
@case('trash')<path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7M14 10v7"/>@break
@case('arrow')<path d="M4 12h16m-6-6 6 6-6 6"/>@break
@case('chevron')<path d="m9 5 7 7-7 7"/>@break
@case('menu')<path d="M4 6h16M4 12h16M4 18h16"/>@break
@case('rupee')<path d="M6 4h12M6 8h12M8 4c8 0 8 8 0 8H6l10 9"/>@break
@case('box')<path d="m12 3 9 5v9l-9 5-9-5V8zM3 8l9 5 9-5M12 13v9M7 5.8l10 5"/>@break
@case('check')<path d="m5 12 4 4L19 6"/>@break
@case('sun')<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.42 1.42M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.42-1.42M17.66 6.34l1.41-1.41"/>@break
@case('moon')<path d="M20.5 14.3A8.5 8.5 0 019.7 3.5a8.5 8.5 0 1010.8 10.8z"/>@break
@case('calculator')<rect x="4" y="2.5" width="16" height="19" rx="2"/><rect x="7" y="5.5" width="10" height="4" rx="1"/><path d="M8 13h.01M12 13h.01M16 13h.01M8 17h.01M12 17h.01M16 17h.01"/>@break
@case('task-list')<path d="M9 4h6l1 3H8z"/><path d="M7 5H5v16h14V5h-2M8 12l2 2 4-4M8 18h7"/>@break
@case('call-log')<path d="M7 3H4a1 1 0 00-1 1c0 9.4 7.6 17 17 17a1 1 0 001-1v-3l-5-2-2 2a15 15 0 01-7-7l2-2z"/><path d="M14 4h7M18 1l3 3-3 3"/>@break
@case('chart')<path d="M12 3a9 9 0 1 0 9 9h-9z"/><path d="M14 3.3A9 9 0 0 1 20.7 10H14z"/>@break
@endswitch
</svg>
