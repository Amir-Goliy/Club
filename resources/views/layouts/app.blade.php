<x-layouts::app.header :title="$title ?? null">
	<flux:main>
		<flux:container>
			{{ $slot }}
		</flux:container>
	</flux:main>
</x-layouts::app.header>
