@props([
    'referenciaUrl',
    'nome' => 'sócio',
])

{{-- Estilo catraca: câmera abre sozinha e libera quando reconhecer o rosto. --}}
<div
    wire:ignore
    x-data="{
        status: 'preparando',
        detalhe: 'Carregando reconhecimento…',
        stream: null,
        refDesc: null,
        loop: null,
        acertos: 0,
        liberado: false,
        async init() {
            await this.iniciar();
        },
        async iniciar() {
            this.liberado = false;
            this.acertos = 0;
            this.status = 'preparando';
            this.detalhe = 'Carregando reconhecimento…';
            try {
                if (! window.FilaPlayFacial) {
                    throw new Error('Biblioteca facial não carregou. Atualize a página (Ctrl+F5).');
                }
                await window.FilaPlayFacial.garantirFaceApi();
                this.detalhe = 'Lendo foto cadastrada de {{ $nome }}…';
                this.refDesc = await window.FilaPlayFacial.descriptorDeUrl(@js($referenciaUrl));
                this.stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'user', width: { ideal: 1280 }, height: { ideal: 960 } },
                    audio: false,
                });
                await this.$nextTick();
                this.$refs.video.srcObject = this.stream;
                await this.$refs.video.play();
                this.status = 'olhando';
                this.detalhe = 'Olhe para a câmera…';
                this.monitorar();
            } catch (e) {
                this.status = 'erro';
                this.detalhe = e?.message || 'Não foi possível iniciar a câmera.';
            }
        },
        monitorar() {
            if (this.loop) clearInterval(this.loop);
            this.loop = setInterval(async () => {
                if (this.liberado || this.status === 'erro') return;
                try {
                    const vivo = await window.FilaPlayFacial.descriptorDoVideo(this.$refs.video);
                    if (! vivo) {
                        this.detalhe = 'Centralize o rosto na câmera…';
                        this.acertos = 0;
                        return;
                    }
                    const dist = window.FilaPlayFacial.distancia(this.refDesc, vivo);
                    if (dist <= 0.55) {
                        this.acertos++;
                        this.detalhe = 'Reconhecendo… (' + this.acertos + '/2)';
                        if (this.acertos >= 2) {
                            await this.liberar();
                        }
                    } else {
                        this.acertos = 0;
                        this.detalhe = dist < 0.75
                            ? 'Quase… aproxime um pouco o rosto.'
                            : 'Rosto diferente do cadastro. Olhe de frente para a câmera.';
                    }
                } catch (e) {
                    // Mantém tentando no próximo ciclo.
                }
            }, 450);
        },
        async liberar() {
            if (this.liberado) return;
            this.liberado = true;
            this.status = 'ok';
            this.detalhe = 'Reconhecido! Liberando…';
            if (this.loop) {
                clearInterval(this.loop);
                this.loop = null;
            }
            this.pararCamera();
            await $wire.confirmarJogadorPorFacial();
        },
        pararCamera() {
            if (this.stream) {
                this.stream.getTracks().forEach(t => t.stop());
                this.stream = null;
            }
            if (this.$refs.video) this.$refs.video.srcObject = null;
        },
        destruir() {
            if (this.loop) clearInterval(this.loop);
            this.pararCamera();
        }
    }"
    x-on:livewire:navigating.window="destruir()"
    x-on:beforeunload.window="destruir()"
    {{ $attributes->class('space-y-3') }}
>
    <div class="overflow-hidden rounded-2xl border-2 border-brand/50 bg-black"
         style="min-height: 22rem;">
        <div class="relative bg-black" style="height: 22rem;">
            <video x-ref="video" playsinline muted autoplay
                   class="absolute inset-0 h-full w-full object-cover scale-x-[-1]"></video>

            <div class="pointer-events-none absolute inset-x-0 top-0 flex justify-center p-3">
                <span class="rounded-full px-4 py-1.5 text-sm font-black"
                      :class="{
                          'bg-black/70 text-white': status === 'olhando' || status === 'preparando',
                          'bg-brand text-canvas': status === 'ok',
                          'bg-red-500/90 text-white': status === 'erro',
                      }"
                      x-text="detalhe"></span>
            </div>

            <div class="pointer-events-none absolute inset-0 flex items-center justify-center"
                 x-show="status === 'preparando'">
                <div class="h-12 w-12 animate-spin rounded-full border-4 border-brand border-t-transparent"></div>
            </div>

            {{-- Guia oval estilo catraca --}}
            <div class="pointer-events-none absolute inset-0 flex items-center justify-center" x-show="status === 'olhando'">
                <div class="h-56 w-44 rounded-[50%] border-4 border-brand/70 shadow-[0_0_0_999px_rgba(0,0,0,0.35)]"></div>
            </div>
        </div>
    </div>

    <p class="text-center text-sm text-muted">
        Como numa catraca: olhe para a câmera. Quando reconhecer <span class="font-semibold text-white">{{ $nome }}</span>, libera sozinho.
    </p>

    <button type="button" x-show="status === 'erro'" @click="iniciar()"
            class="fp-btn inline-flex min-h-12 w-full cursor-pointer items-center justify-center rounded-2xl bg-brand text-base font-black text-canvas">
        Tentar de novo
    </button>
</div>
