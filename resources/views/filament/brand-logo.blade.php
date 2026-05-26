<div class="flex flex-col items-center justify-center text-center gap-y-3">

    <div class="flex items-center gap-x-3">
        <img src="{{ asset('images/logo.png') }}" alt="Logo SIDAPIL" class="h-10 w-auto">
        <span class="text-2xl font-bold tracking-widest text-white uppercase"
            style="font-family: 'Outfit', sans-serif;">
            SIDAPIL
        </span>
    </div>

    @if(request()->routeIs('filament.admin.auth.login'))
        <p class="mt-5 mb-4 text-white font-bold tracking-wider text-sm max-w-[300px] leading-relaxed opacity-90">
            Sistem Pendaftaran Kependudukan dan Pencatatan Sipil Disdukcapil Klaten
        </p>
    @endif


</div>