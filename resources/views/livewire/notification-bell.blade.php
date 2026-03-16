<div wire:poll.30s="loadNotifications">
    <flux:dropdown position="top" align="start">
        {{-- Bell trigger --}}
        <flux:button
            variant="ghost"
            size="sm"
            class="relative !h-10 !w-10 rounded-full"
            x-on:click="$wire.loadNotifications()"
            aria-label="Notifications"
        >
            <flux:icon.bell class="size-5" />

            @if($unreadCount > 0)
                <span class="absolute top-1 right-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/50 px-1 text-[10px] font-bold leading-none text-red-700 dark:text-red-300">
                    {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                </span>
            @endif
        </flux:button>

        {{-- Dropdown panel --}}
        <flux:menu class="w-80 max-h-[26rem] overflow-y-auto p-0">
            {{-- Header --}}
            <div class="flex items-center justify-between border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
                <span class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">Notifications</span>
                @if(auth()->user()->isStudent() && $unreadCount > 0 && collect($notifications)->where('type', 'student')->isNotEmpty())
                    <button
                        wire:click="markAsSeen"
                        class="text-xs text-blue-600 hover:underline dark:text-blue-400"
                    >
                        Mark all as read
                    </button>
                @endif
            </div>

            {{-- Empty state --}}
            @if(empty($notifications))
                <div class="flex flex-col items-center justify-center gap-2 px-4 py-8 text-center text-zinc-400 dark:text-zinc-500">
                    <flux:icon.bell-slash class="size-8 opacity-50" />
                    <span class="text-sm">No notifications</span>
                </div>

            {{-- Unified list — each item knows its own type --}}
            @else
                <ul class="divide-y divide-zinc-100 dark:divide-zinc-700">
                    @foreach($notifications as $notif)
                        <li>
                        {{-- Student clearance status item --}}
                        @if($notif['type'] === 'student')
                        <a href="{{ $notif['url'] }}" wire:navigate
                           class="flex items-start gap-3 px-4 py-3 hover:bg-zinc-50 dark:hover:bg-zinc-800/60 transition-colors relative">
                            {{-- Status icon --}}
                            <div class="mt-0.5 shrink-0">
                                @if($notif['status'] === 'approved')
                                    <span class="flex h-7 w-7 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/40">
                                        <flux:icon.check class="size-4 text-green-600 dark:text-green-400" />
                                    </span>
                                @elseif($notif['status'] === 'rejected')
                                    <span class="flex h-7 w-7 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/40">
                                        <flux:icon.x-mark class="size-4 text-red-600 dark:text-red-400" />
                                    </span>
                                @else
                                    <span class="flex h-7 w-7 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-700">
                                        <flux:icon.clock class="size-4 text-zinc-500 dark:text-zinc-400" />
                                    </span>
                                @endif
                            </div>

                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100 leading-snug">
                                    {{ $notif['entity'] }}
                                </p>

                                <p class="text-xs mt-0.5
                                    @if($notif['status'] === 'approved') text-green-600 dark:text-green-400
                                    @elseif($notif['status'] === 'rejected') text-red-600 dark:text-red-400
                                    @else text-zinc-500 dark:text-zinc-400
                                    @endif font-medium capitalize">
                                    {{ $notif['status'] }}
                                </p>

                                @if(!empty($notif['remarks']))
                                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400 truncate" title="{{ $notif['remarks'] }}">
                                        {{ $notif['remarks'] }}
                                    </p>
                                @endif

                                <p class="mt-1 text-[11px] text-zinc-400 dark:text-zinc-500">
                                    {{ $notif['time'] }}
                                </p>
                            </div>

                            @if($notif['is_new'])
                                <span class="mt-2 h-2 w-2 shrink-0 rounded-full bg-blue-50 dark:bg-blue-500/100"></span>
                            @endif
                        </a>

                        {{-- Signatory pending item --}}
                        @else
                        <a href="{{ $notif['url'] }}" wire:navigate
                           class="flex items-center gap-3 px-4 py-3 hover:bg-zinc-50 dark:hover:bg-zinc-800/60 transition-colors">
                            {{-- Entity badge --}}
                            <span class="shrink-0 rounded-md bg-zinc-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400">
                                {{ $notif['label'] }}
                            </span>

                            <div class="flex-1 min-w-0">
                                <p class="truncate text-sm font-medium text-zinc-800 dark:text-zinc-100">
                                    {{ $notif['entity'] }}
                                </p>
                                <p class="text-xs text-orange-600 dark:text-orange-400">
                                    {{ $notif['display'] }} student{{ (int)$notif['pending'] === 1 ? '' : 's' }} ready to sign
                                </p>
                            </div>

                            {{-- Count badge --}}
                            <span class="shrink-0 flex h-6 min-w-6 items-center justify-center rounded-full bg-orange-100 dark:bg-orange-900/40 px-1.5 text-xs font-bold text-orange-700 dark:text-orange-300">
                                {{ $notif['display'] }}
                            </span>
                        </a>
                        @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </flux:menu>
    </flux:dropdown>
</div>
