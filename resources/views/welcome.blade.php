@extends('layouts.guest')

@section('content')
    <div class="d-flex justify-content-center">
        <div class="col-md-10">
            <x-flash-success :message="session('success')" />

            <div class="card shadow-sm border-white mb-4 px-4">
                <div>
                    <h1 class="fs-1 card-title mb-3 text-center">Welkom op {{ config('app.name') }}</h1>
                </div>
        
                    <p class="text-break">
                        Deze site is voornamelijk bedoeld als een demo van verschillende onderdelen die ik heb gemaakt.
                        Deze onderdelen zijn afkomstig van de projecten die ik in de afgelopen jaren heb opgebouwd om nieuw dingen te leren, veel van deze projecten staan op mijn gitlab pagina.
                    </p>

                    <p class="text-break">
                        Voor het maken van de projecten heb ik bijna altijd gebruik gemaakt van het Laravel framework in combinate met MariaDB. Voor de voorkant van de applicaties heb ik in het verleden gebruik gemaakt van Tailwindcss,
                        in mijn meer recente projecten maak ik gebruik van Bootstrap.
                    </p>
                    <p class="text-break">
                        Tijdens het ontwikkelen van de verschillende heb ik ook gebruik gemaakt van (Laravel) packages. Een aantal voorbeelden hiervan zijn:
                        <ul>
                            <li>Laravel-permission van spatie</li>
                            <li>Laravel-medialibrary van Spatie</li>
                            <li>Laravel Telescope van Laravel</li>
                            <li>Larastarters van LaravelDaily</li>
                        </ul>
                    </p>

                    Via de links hieronder kunt u voorbeelden van mijn code vinden
                    <ul>
                        <li><a href="https://github.com/jacoen">Github</a></li>
                        <li><a href="https://gitlab.com/j.coenraad">Gitlab</li>
                    </ul>
            </div>
        </div>
    </div>
@endsection