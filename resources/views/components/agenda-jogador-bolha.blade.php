@if ($jogador['foto'])
    <img src="{{ $jogador['foto'] }}" alt="{{ $jogador['nome'] }}" class="h-12 w-12 rounded-full object-cover ring-2 ring-canvas">
@else
    <span class="flex h-12 w-12 items-center justify-center rounded-full bg-brand text-sm font-black text-canvas ring-2 ring-canvas">{{ $jogador['iniciais'] }}</span>
@endif
