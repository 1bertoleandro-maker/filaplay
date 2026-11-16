{{--
    Identificação 1:N no tablet: o atleta só olha para a câmera, o sistema descobre quem é
    (nome + código) e ele toca em OK. Os vetores faciais são calculados uma única vez
    e guardados no servidor, então as próximas leituras são instantâneas.
--}}
<div
    wire:ignore
    x-data="{
        status: 'preparando',
        detalhe: 'Carregando reconhecimento…',
        stream: null,
        loop: null,
        candidatos: [],
        prontos: 0,
        total: 0,
        ultimo: null,
        acertos: 0,
        pausado: false,
        ocupado: false,
        encerrado: false,
        async init() {
            await this.iniciar();
        },
        async iniciar() {
            this.status = 'preparando';
            this.detalhe = 'Carregando reconhecimento…';
            try {
                if (! window.FilaPlayFacial) {
                    throw new Error('Biblioteca facial não carregou. Atualize a página (Ctrl+F5).');
                }
                await window.FilaPlayFacial.garantirFaceApi();
                const lista = await $wire.carregarCandidatos();
                this.candidatos = lista.map(c => ({ ...c, vetor: c.descritor ? Float32Array.from(c.descritor) : null }));
                this.total = this.candidatos.length;
                this.prontos = this.candidatos.filter(c => c.vetor).length;

                if (this.total === 0) {
                    this.status = 'vazio';
                    this.detalhe = 'Nenhum atleta com facial cadastrada ainda.';
                    return;
                }

                this.stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'user', width: { ideal: 1280 }, height: { ideal: 960 } },
                    audio: false,
                });
                await this.$nextTick();
                if (this.encerrado) { this.pararCamera(); return; }
                this.$refs.video.srcObject = this.stream;
                await this.$refs.video.play();
                this.status = 'olhando';
                this.detalhe = 'Olhe para a câmera…';
                this.monitorar();
                this.preparar();
            } catch (e) {
                this.status = 'erro';
                this.detalhe = e?.message || 'Não foi possível iniciar a câmera.';
            }
        },
        async preparar() {
            for (const c of this.candidatos) {
                if (this.encerrado) return;
                if (c.vetor || c.falhou) continue;
                try {
                    c.vetor = await window.FilaPlayFacial.descriptorDeUrl(c.face);
                    this.prontos++;
                    $wire.salvarDescritor(c.id, Array.from(c.vetor)).catch(() => {});
                } catch (e) {
                    c.falhou = true;
                }
                await new Promise(r => setTimeout(r, 40));
            }
        },
        monitorar() {
            if (this.loop) clearInterval(this.loop);
            this.loop = setInterval(async () => {
                if (this.pausado || this.ocupado || this.encerrado || this.status === 'erro') return;
                this.ocupado = true;
                try {
                    const vivo = await window.FilaPlayFacial.descriptorDoVideo(this.$refs.video);
                    if (! vivo) {
                        this.detalhe = 'Centralize o rosto na câmera…';
                        this.acertos = 0;
                        this.ultimo = null;
                        return;
                    }
                    let melhor = null, d1 = 99, d2 = 99;
                    for (const c of this.candidatos) {
                        if (! c.vetor) continue;
                        const d = window.FilaPlayFacial.distancia(vivo, c.vetor);
                        if (d < d1) { d2 = d1; d1 = d; melhor = c; }
                        else if (d < d2) { d2 = d; }
                    }
                    if (melhor && d1 <= 0.52 && (d2 - d1) >= 0.04) {
                        if (this.ultimo === melhor.id) { this.acertos++; } else { this.ultimo = melhor.id; this.acertos = 1; }
                        this.detalhe = 'Reconhecendo…';
                        if (this.acertos >= 2) {
                            await this.achou(melhor);
                        }
                    } else {
                        this.acertos = 0;
                        this.ultimo = null;
                        this.detalhe = this.prontos < this.total
                            ? 'Procurando… ' + this.prontos + ' de ' + this.total + ' rostos prontos'
                            : 'Não reconheci. Chegue mais perto ou digite o código.';
                    }
                } catch (e) {
                    // tenta de novo no próximo ciclo
                } finally {
                    this.ocupado = false;
                }
            }, 500);
        },
        async achou(candidato) {
            this.pausado = true;
            this.status = 'ok';
            this.detalhe = 'Achei você!';
            await $wire.identificarPorRosto(candidato.id);
            // Se o servidor recusou (ex.: já está em outra quadra), volta a procurar.
            if (! $wire.identificado || Object.keys($wire.identificado).length === 0) {
                this.status = 'olhando';
                this.acertos = 0;
                this.ultimo = null;
                this.pausado = false;
            }
        },
        pararCamera() {
            if (this.stream) {
                this.stream.getTracks().forEach(t => t.stop());
                this.stream = null;
            }
            if (this.$refs.video) this.$refs.video.srcObject = null;
        },
        destroy() {
            this.encerrado = true;
            if (this.loop) clearInterval(this.loop);
            this.pararCamera();
        }
    }"
    {{ $attributes->class('space-y-3') }}
>
    <div class="overflow-hidden rounded-2xl border-2 border-brand/50 bg-black">
        <div class="relative bg-black" style="height: 24rem;">
            <video x-ref="video" playsinline muted autoplay
                   class="absolute inset-0 h-full w-full scale-x-[-1] object-cover"></video>

            <div class="pointer-events-none absolute inset-x-0 top-0 flex justify-center p-3">
                <span class="rounded-full px-4 py-1.5 text-sm font-black"
                      :class="{
                          'bg-black/70 text-white': status === 'olhando' || status === 'preparando' || status === 'vazio',
                          'bg-brand text-canvas': status === 'ok',
                          'bg-red-500/90 text-white': status === 'erro',
                      }"
                      x-text="detalhe"></span>
            </div>

            <div class="pointer-events-none absolute inset-0 flex items-center justify-center" x-show="status === 'preparando'">
                <div class="h-12 w-12 animate-spin rounded-full border-4 border-brand border-t-transparent"></div>
            </div>

            <div class="pointer-events-none absolute inset-0 flex items-center justify-center" x-show="status === 'olhando' || status === 'ok'">
                <div class="h-60 w-48 rounded-[50%] border-4 shadow-[0_0_0_999px_rgba(0,0,0,0.35)]"
                     :class="status === 'ok' ? 'border-brand' : 'border-brand/60'"></div>
            </div>
        </div>
    </div>

    <button type="button" x-show="status === 'erro'" x-cloak @click="iniciar()"
            class="fp-btn inline-flex min-h-12 w-full cursor-pointer items-center justify-center rounded-2xl bg-brand text-base font-black text-canvas">
        Tentar de novo
    </button>
</div>
