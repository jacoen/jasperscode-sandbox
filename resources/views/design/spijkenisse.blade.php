<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
		<title>Basic HTML5 document</title>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<script src="https://cdn.tailwindcss.com"></script>
		<script>
		</script>
	</head>
	<body class="font-sans antialiased">
		<div class="flex flex-col min-h-screen bg-[#f0f0f0]">
			<header class="h-16">
				<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    <h1 class="text-4xl font-bold mb-2 text-[#ffb005] ml-40">
                        {Spijkenisse};
                    </h1>
                </div>
			</header>

			<div class="max-w-7xl mx-auto mt-4">
				<div class="flex flex-1 overflow-hidden">

					<aside class="block w-40 fixed top-20 left-26 transition-top transition-all duration-100">
                        <div class="my-2 mx-1 text-right text-lg font-semibold">
                            <div class="py-2">
                                <a href="#" class="px-3 py-1 font-semibold rounded-md hover:bg-gray-800 hover:text-white">
                                    Home
                                </a>
                            </div>
                            <div class="py-2">
                                <a href="#" class="px-3 py-1 font-semibold rounded-md hover:bg-gray-800 hover:text-white">
                                    About
                                </a>
                            </div>
                            <div class="py-2">
                                <a href="#" class="px-3 py-1 font-semibold rounded-md hover:bg-gray-800 hover:text-white">
                                    Blog
                                </a>
                            </div>
                            <div class="py-2">
                                <a href="#" class="px-3 py-1 font-semibold rounded-md hover:bg-gray-800 hover:text-white">
                                    Contact
                                </a>
                            </div>
                        </div>
                    </aside>

					<main class="flex flex-1 overflow-y-auto mb-4 ml-40 px-4 mx-auto max-w-6xl">

						<div class="bg-white shadow-lg rounded-md px-4 pt-3">
							<h1 class="block text-center text-2xl font-extrabold text-slate-800 tracking-tight mb-3">
								Welkom bij coderdojo Spijkenisse
							</h1>
							<p class="mb-4">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Praesent a ex non libero mollis tristique. Proin sollicitudin ipsum sit amet augue rhoncus lacinia. Vivamus a placerat felis, eget porttitor velit. Vestibulum libero nisl, mollis facilisis suscipit ac, blandit eu turpis. Duis ac egestas nibh, quis pretium eros. Morbi sed ante sagittis, elementum magna vel, laoreet est. Praesent faucibus interdum commodo. Morbi euismod interdum lacus, vitae tincidunt justo ultrices eu. Nunc vestibulum accumsan erat ac sodales. Maecenas quis accumsan lectus. Cras magna leo, malesuada eu urna nec, dictum ultricies massa. Pellentesque ac eros ut orci aliquam consectetur eu sit amet est. Donec semper lacus orci, vitae viverra risus accumsan vel. Fusce tristique lobortis malesuada. Proin id malesuada libero, in vestibulum nunc. Maecenas in nisi lorem. </p>
							<hr>
							<div class="my-2">
								<div class="grid grid-cols-3 gap-6">
									<div>
										<h2 class="text-lg font-bold tracking-tight mb-2">
											Leer gratis programmeren
										</h2>
										<p>
											Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Sed rutrum volutpat mauris, vel vulputate odio dignissim in. Integer sit amet fermentum elit, eget tincidunt augue. Quisque vitae augue ac ante hendrerit tempor nec quis nunc. Pellentesque ac sollicitudin lorem. Etiam non sem eleifend, facilisis mi sit amet, mattis leo. Vivamus vulputate auctor massa, id imperdiet ipsum finibus vitae. Phasellus molestie sem sed justo commodo pretium. Vestibulum rhoncus ut leo in accumsan. Aliquam congue lorem massa, sit amet facilisis diam lobortis id.
										</p>
									</div>
									<div>
										<h2 class="text-lg font-bold tracking-tight mb-2">
											Kom in contact
										</h2>
										<p>
											Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Sed rutrum volutpat mauris, vel vulputate odio dignissim in. Integer sit amet fermentum elit, eget tincidunt augue. Quisque vitae augue ac ante hendrerit tempor nec quis nunc. Pellentesque ac sollicitudin lorem. Etiam non sem eleifend, facilisis mi sit amet, mattis leo. Vivamus vulputate auctor massa, id imperdiet ipsum finibus vitae. Phasellus molestie sem sed justo commodo pretium. Vestibulum rhoncus ut leo in accumsan. Aliquam congue lorem massa, sit amet facilisis diam lobortis id.
										</p>
									</div>
									<div>
										<h2 class="text-lg font-bold tracking-tight mb-2">
											Help anderen
										</h2>
						
										<p>
											Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Sed rutrum volutpat mauris, vel vulputate odio dignissim in. Integer sit amet fermentum elit, eget tincidunt augue. Quisque vitae augue ac ante hendrerit tempor nec quis nunc. Pellentesque ac sollicitudin lorem. Etiam non sem eleifend, facilisis mi sit amet, mattis leo. Vivamus vulputate auctor massa, id imperdiet ipsum finibus vitae. Phasellus molestie sem sed justo commodo pretium. Vestibulum rhoncus ut leo in accumsan. Aliquam congue lorem massa, sit amet facilisis diam lobortis id.
										</p>
									</div>
								</div>
							</div> 
							<hr>
							<div class="my-2">
								<h2 class="text-lg font-bold tracking-tight mb-2">
									Komende edities
								</h2>
						
								<div class="grid grid-cols-3 gap-4">
									<div class="border border-slate-300 rounded-lg inline-block align-middle py-4 px-4 text-center text-gray-900 bg-slate-200">
										<div class="text-lg font-semibold">N.T.B</div>
										<div class="text-sm">
											De Ruimte
										</div>
									</div>
						
									<div class="border border-slate-300 rounded-lg inline-block align-middle py-4 px-4 text-center text-gray-900 bg-slate-200">
										<div class="text-lg font-semibold">N.T.B</div>
										<div class="text-sm">
											De Ruimte
										</div>
									</div>
						
									<div class="border border-slate-300 rounded-lg inline-block align-middle py-4 px-4 text-center text-gray-900 bg-slate-200">
										<div class="text-lg font-semibold">N.T.B</div>
										<div class="text-sm">
											De Ruimte
										</div>
									</div>
								</div>
							</div>
							<div class="my-2">
								<h2 class="text-lg font-bold tracking-tight mb-2">
									Inschrijven
								</h2>
						
								<div class="my-1">
									<span class="text-sm font-semibold">
										TIP: Is uw kind nog relatief jong (ca. 7-9 jaar) en heeft uw kind nog nooit geprogrammeerd? Neem dan (indien mogelijk) een tablet mee ipv laptop. Daarop kunnen wij wat eenvoudigere beginners-oefeningen aanbieden. Daarna kunnen we dan opstappen naar de laptop.
									</span>
								</div>
						
								<div class="my-1">
									<div class="border border-dashed rounded-lg border-slate-800 bg-gray-300">
										<div class="text-lg font-bold text-center py-2 px-2">
											Komende dojo: N.T.B.
										</div>
									</div>
								</div>
						
								<div>
									<form>
										<div class="w-1/2">
											<div class="mb-2">
												<label for="name" class="block font-medium leading-6 text-gray-900">Naam</label>
												<input type="text" name="name" id="name" class="block w-full mt-1 border border-gray-300 focus:border-indigo-300 rounded-md shadow-sm">
											</div>
						
											<div class="mb-2">
												<label for="email" class="block font-medium leading-6 text-gray-900">E-mail adres</label>
												<input type="email" name="email" id="email" class="block w-full mt-1 border border-gray-300 focus:border-indigo-300 rounded-md shadow-sm">
											</div>
						
											<div class="mb-2">
												<label for="phone" class="block font-medium leading-6 text-gray-900">Telefoon</label>
												<input type="phone" name="phone" id="phone" class="block w-full mt-1 border border-gray-300 focus:border-indigo-300 rounded-md shadow-sm">
											</div>
										</div>
									</form>
									<div class="w-1/2">
										<div class="flex items-center justify-end mt-4">
											<button class="inline-flex px-2 py-2 bg-[#d1410c] text-white font-semibold rounded-lg">
												Inschrijven
											</button>
										</div>
									</div>
								</div>
							</div>
						
							<hr>
						
							<div class="my-3">
								<h2 class="text-lg font-bold tracking-tight mb-2">
									Komende edities
								</h2>
						
								De dojo’s vinden plaatsen in <a href="#" class="font-semibold">bibliotheek De Boekenberg</a> te Spijkenisse (aan de Markt). Wij zitten normaal in een ruimte genaamd “De Ruimte”, deze locatie is te bereiken als volgt;
								<ul class="list-disc ml-8">
									<li>Bij binnenkomst via de hoofdingang (draaideur) links de trap op.</li>
									<li>Volg de glazen buitenmuur langs Café Zinnig</li>
									<li>Loop door tot aan het Schaaklokaal (aan de rechterhand)</li>
									<li>Ga naar binnen en steek de ruimte dwars door (tot aan de toiletten)</li>
									<li>Met een korte slinger naar links en rechts sta je bij “De Ruimte</li>
								</ul>
							</div>
						
							<div class="my-3">
								<h2 class="text-lg font-bold tracking-tight mb-2">
									Komende edities
								</h2>
								<ul class="list-disc ml-8">
									<li>Ga om met anderen zoals je zelf ook behandeld wilt worden.</li>
									<li>Niet eten en/of drinken bij de laptops of robots</li>
									<li>Ruim je rommel op als je ergens klaar mee bent</li>
									<li>Niet gamen (tenzij we het aangeven). Gamen kan thuis ook, we willen je tijdens de dojo echt iets leren.</li>
								</ul>
							</div>
						
							<div class="my-2 pb-4">
								<h2 class="text-lg font-bold tracking-tight mb-2">
									Vragen/Contact
								</h2>
								<p>Facebook: CoderDojo Spijkenisse</p>
								<p>Email: info [🐒] coderdojo-spijkenisse [⏺] nl</p>
							</div>
						</div>
					</main>
				</div>
			</div>

			<footer class="bg-gray-700 h-14">
                <div class="max-w-7xl mx-auto pt-2 px-4 sm:px-6 lg:px-8">
                    <h2 class="text-2xl font-semibold mb-2 text-white ml-40">
                        Footer
                    </h2>
                </div>
            </footer>

		</div>

		<script>
            window.addEventListener('scroll', function() {
                var header = document.querySelector('header');
                var aside = document.querySelector('aside');
                
                if (window.scrollY > header.offsetHeight) {
                    aside.classList.add('top-0');
                    aside.classList.remove('top-20');
                } else {
                    aside.classList.add('top-20');
                    aside.classList.remove('top-0');
                }
            });
        </script>
	</body>
</html>