<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center gap-4">
            <div class="flex-shrink-0">
                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center shadow-lg">
                    <span class="text-white font-bold text-2xl">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </span>
                </div>
            </div>
            <div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                    Selamat Datang {{ auth()->user()->name }}! 👋
                </h2>
                <p class="text-gray-500 dark:text-gray-400">
                    Anda login sebagai <span class="font-semibold text-primary-600 dark:text-primary-400">{{ auth()->user()->role_label }}</span>
                </p>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
