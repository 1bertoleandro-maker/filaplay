@props([
    'model' => 'foto',
    'rotulo' => 'Ligar câmera',
    'dica' => 'Autorize a câmera quando o navegador pedir.',
    'compacto' => false,
    'tamanho' => null, // 'compacto' | 'grande' | null (padrão)
])

@php
    $modo = $tamanho ?: ($compacto ? 'compacto' : 'padrao');
    $altura = match ($modo) {
        'compacto' => '14rem',
        'grande' => '28rem',
        default => '20rem',
    };
    $btnSm = $modo === 'compacto';
@endphp

{{-- Webcam real (getUserMedia). Preview alto o bastante para o rosto não cortar. --}}
<div
    wire:ignore.self
    x-data="{
        stream: null,
        erro: '',
        tirando: false,
        preview: null,
        async iniciar() {
            this.erro = '';
            this.preview = null;
            try {
                if (! navigator.mediaDevices?.getUserMedia) {
                    this.erro = 'Este navegador não libera a câmera. Use Chrome/Edge ou envie um arquivo.';
                    return;
                }
                this.parar();
                this.stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'user', width: { ideal: 1280 }, height: { ideal: 960 } },
                    audio: false,
                });
                await this.$nextTick();
                if (this.$refs.video) {
                    this.$refs.video.srcObject = this.stream;
                    await this.$refs.video.play();
                }
            } catch (e) {
                this.erro = 'Não foi possível abrir a câmera. Verifique a permissão ou use Arquivo.';
            }
        },
        parar() {
            if (this.stream) {
                this.stream.getTracks().forEach(t => t.stop());
                this.stream = null;
            }
            if (this.$refs.video) this.$refs.video.srcObject = null;
        },
        async capturar() {
            if (! this.$refs.video?.videoWidth) return;
            this.tirando = true;
            const video = this.$refs.video;
            const canvas = this.$refs.canvas;
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0);
            this.preview = canvas.toDataURL('image/jpeg', 0.92);
            const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', 0.92));
            const arquivo = new File([blob], 'rosto-' + Date.now() + '.jpg', { type: 'image/jpeg' });
            $wire.upload('{{ $model }}', arquivo,
                () => { this.tirando = false; this.parar(); },
                () => { this.tirando = false; this.erro = 'Falha ao enviar a foto. Tente de novo.'; }
            );
        },
        trocar() {
            this.preview = null;
            this.erro = '';
            this.iniciar();
        },
        escolherArquivo(event) {
            const arquivo = event.target.files?.[0];
            if (! arquivo) return;
            this.preview = URL.createObjectURL(arquivo);
            this.parar();
            $wire.upload('{{ $model }}', arquivo);
            event.target.value = '';
        }
    }"
    x-on:livewire:navigating.window="parar()"
    x-on:beforeunload.window="parar()"
    {{ $attributes->class('space-y-2') }}
>
    <canvas x-ref="canvas" class="hidden"></canvas>

    <div class="flex flex-wrap gap-2">
        <button type="button"
                x-show="! stream && ! preview"
                @click="iniciar()"
                @class([
                    'inline-flex cursor-pointer items-center justify-center gap-2 bg-brand font-black text-canvas hover:brightness-110 active:scale-95',
                    'min-h-11 flex-1 rounded-xl px-4 text-sm' => $btnSm,
                    'min-h-14 flex-1 rounded-2xl px-6 text-lg shadow-lg shadow-brand/25' => ! $btnSm,
                ])>
            <x-heroicon-o-video-camera @class([$btnSm ? 'h-4 w-4' : 'h-6 w-6']) />
            {{ $rotulo }}
        </button>

        <button type="button"
                x-show="stream && ! preview"
                @click="capturar()"
                :disabled="tirando"
                @class([
                    'inline-flex flex-1 cursor-pointer items-center justify-center gap-2 bg-brand font-black text-canvas hover:brightness-110 active:scale-95 disabled:opacity-60',
                    'min-h-11 rounded-xl px-4 text-sm' => $btnSm,
                    'min-h-14 rounded-2xl px-6 text-lg' => ! $btnSm,
                ])>
            <x-heroicon-o-camera @class([$btnSm ? 'h-4 w-4' : 'h-6 w-6']) />
            <span x-text="tirando ? 'Enviando…' : 'Tirar foto agora'"></span>
        </button>

        <button type="button"
                x-show="preview"
                @click="trocar()"
                @class([
                    'inline-flex flex-1 cursor-pointer items-center justify-center gap-2 border border-line font-bold hover:bg-canvas active:scale-95',
                    'min-h-11 rounded-xl px-4 text-sm' => $btnSm,
                    'min-h-14 rounded-2xl px-6 text-lg' => ! $btnSm,
                ])>
            Tirar outra
        </button>

        <label @class([
            'inline-flex cursor-pointer items-center justify-center gap-2 border border-line font-semibold hover:bg-canvas active:scale-95',
            'min-h-11 rounded-xl px-4 text-sm' => $btnSm,
            'min-h-14 rounded-2xl px-5 text-base' => ! $btnSm,
        ])>
            <x-heroicon-o-folder-open @class([$btnSm ? 'h-4 w-4' : 'h-5 w-5']) />
            Arquivo
            <input type="file" accept="image/*" class="sr-only" @change="escolherArquivo($event)">
        </label>
    </div>

    <p class="text-xs text-muted">{{ $dica }}</p>
    <p x-show="erro" class="text-sm text-red-400" x-text="erro"></p>

    <div
        class="overflow-hidden rounded-2xl border border-dashed border-brand/40 bg-black/40"
        style="min-height: {{ $altura }};"
        x-show="stream || preview"
        x-cloak
    >
        <div class="relative bg-black" style="height: {{ $altura }};">
            {{-- object-contain: mostra o rosto inteiro, sem cortar --}}
            <video x-ref="video" playsinline muted autoplay
                   class="absolute inset-0 h-full w-full object-contain scale-x-[-1]"
                   x-show="stream && ! preview"></video>
            <img x-show="preview" :src="preview" alt="Prévia do rosto"
                 class="absolute inset-0 h-full w-full object-contain scale-x-[-1]">
            <div x-show="stream && ! preview"
                 class="pointer-events-none absolute inset-x-0 bottom-0 flex justify-center pb-3">
                <span class="rounded-full bg-black/60 px-3 py-1 text-xs font-bold text-white">
                    Centralize o rosto e tire a foto
                </span>
            </div>
        </div>
    </div>
</div>
