<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center gap-4">
            <div class="flex-shrink-0">
                <div class="w-14 h-14 rounded-2xl bg-primary-100 dark:bg-primary-600 flex items-center justify-center shadow-sm">
                    <span class="font-bold text-2xl welcome-avatar-text">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </span>
                </div>
            </div>
            <div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white welcome-title">
                    Selamat Datang {{ auth()->user()->name }}! 👋
                </h2>
                <p class="text-gray-500 dark:text-gray-400 welcome-subtitle">
                    Anda login sebagai <span class="font-semibold text-primary-600 dark:text-primary-400">{{ auth()->user()->role_label }}</span>
                </p>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
