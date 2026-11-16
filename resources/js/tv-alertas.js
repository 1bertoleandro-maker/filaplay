/**
 * Alertas da TV (estilo senha de hospital):
 *  - "Nova reserva": tela cheia verde, ~12 s, com voz e sinal sonoro;
 *  - "Entrada em 2 minutos": tela cheia amarela, ~60 s, com voz e sinal sonoro.
 * O Livewire atualiza #tv-dados a cada poucos segundos; aqui só reagimos ao que é novo.
 */
const CHAVE_VISTOS = 'fp-tv-vistos';
const CHAVE_ULTIMO = 'fp-tv-ultimo';

function lerSessao(chave, padrao) {
    try {
        const bruto = sessionStorage.getItem(chave);
        return bruto === null ? padrao : JSON.parse(bruto);
    } catch (e) {
        return padrao;
    }
}

function gravarSessao(chave, valor) {
    try {
        sessionStorage.setItem(chave, JSON.stringify(valor));
    } catch (e) {
        // sem armazenamento: tudo bem
    }
}

function falarHora(hhmm) {
    const [h, m] = String(hhmm).split(':').map(Number);

    return m ? `${h} horas e ${m} minutos` : `${h} horas`;
}

document.addEventListener('alpine:init', () => {
    window.Alpine.data('tvAlertas', () => ({
        atual: null,
        fila: [],
        somPronto: false,
        ctx: null,
        vistos: lerSessao(CHAVE_VISTOS, {}),
        ultimo: lerSessao(CHAVE_ULTIMO, null),
        temporizador: null,

        init() {
            this.somPronto = false;
            setInterval(() => this.ler(), 2000);
            setInterval(() => {
                this.somPronto = this.ctx !== null && this.ctx.state === 'running';
            }, 1500);
            this.ler();
        },

        ativarSom() {
            try {
                this.ctx = this.ctx ?? new (window.AudioContext || window.webkitAudioContext)();
                this.ctx.resume().then(() => {
                    this.somPronto = this.ctx.state === 'running';
                    this.sinal(1);
                });

                if ('speechSynthesis' in window) {
                    const teste = new SpeechSynthesisUtterance('Avisos sonoros ativados');
                    teste.lang = 'pt-BR';
                    window.speechSynthesis.speak(teste);
                }
            } catch (e) {
                this.somPronto = false;
            }
        },

        dados() {
            const el = document.getElementById('tv-dados');

            if (! el) {
                return null;
            }

            try {
                return {
                    chamadas: JSON.parse(el.dataset.chamadas || '[]'),
                    novas: JSON.parse(el.dataset.novas || '[]'),
                    ultimo: Number(el.dataset.ultimo || 0),
                };
            } catch (e) {
                return null;
            }
        },

        ler() {
            const d = this.dados();

            if (! d) {
                return;
            }

            // Primeira leitura: não repete o que já existia antes de abrir a TV.
            if (this.ultimo === null) {
                this.ultimo = d.ultimo;
                gravarSessao(CHAVE_ULTIMO, this.ultimo);
            }

            // Novas reservas (mais antigas primeiro).
            d.novas
                .filter(n => n.id > this.ultimo)
                .sort((a, b) => a.id - b.id)
                .forEach(n => {
                    const [horario, ...resto] = String(n.detalhe).split(' · ');
                    const [nomes] = [resto.join(' · ')];
                    this.fila.push({
                        titulo: 'Nova reserva',
                        cor: '#35c759',
                        duracao: 12000,
                        fala: `Atenção. Nova reserva. Quadra ${n.quadra}. ${nomes ? nomes.replace(/,/g, ' e') : ''}.`,
                        cards: [{ quadra: n.quadra, horario: horario.replace(' às ', ' – '), nomes, extra: '' }],
                    });
                });

            if (d.ultimo > this.ultimo) {
                this.ultimo = d.ultimo;
                gravarSessao(CHAVE_ULTIMO, this.ultimo);
            }

            // Entradas em 2 minutos (agrupa as quadras ainda não avisadas).
            const agora = Date.now();
            const novasChamadas = d.chamadas.filter(c => ! this.vistos[c.id]);

            if (novasChamadas.length > 0) {
                novasChamadas.forEach(c => { this.vistos[c.id] = agora; });
                gravarSessao(CHAVE_VISTOS, this.vistos);

                const nomesFala = novasChamadas
                    .map(c => `quadra ${c.quadra}${c.nomes ? ': ' + c.nomes.replace(/ · /g, ', ') : ''}`)
                    .join('. ');

                this.fila.push({
                    titulo: 'Entrada em 2 minutos',
                    cor: '#ffd60a',
                    duracao: 60000,
                    fala: `Atenção. Em dois minutos, entram em jogo: ${nomesFala}.`,
                    cards: novasChamadas.map(c => ({
                        quadra: c.quadra,
                        horario: `${c.inicio} – ${c.fim}`,
                        nomes: c.nomes,
                        extra: c.modalidade,
                    })),
                });
            }

            this.proximo();
        },

        proximo() {
            if (this.atual || this.fila.length === 0) {
                return;
            }

            this.atual = this.fila.shift();
            this.anunciar(this.atual);

            this.temporizador = setTimeout(() => {
                this.atual = null;
                this.proximo();
            }, this.atual.duracao);
        },

        anunciar(alerta) {
            this.sinal(2);

            if (this.somPronto && 'speechSynthesis' in window) {
                setTimeout(() => {
                    const fala = new SpeechSynthesisUtterance(alerta.fala);
                    fala.lang = 'pt-BR';
                    fala.rate = 0.92;
                    window.speechSynthesis.cancel();
                    window.speechSynthesis.speak(fala);
                }, 1400);
            }
        },

        /** "Ding-dong" repetido (n vezes). */
        sinal(vezes = 2) {
            if (! this.ctx || this.ctx.state !== 'running') {
                return;
            }

            const tom = (freq, inicio, duracao) => {
                const osc = this.ctx.createOscillator();
                const ganho = this.ctx.createGain();
                osc.type = 'sine';
                osc.frequency.value = freq;
                ganho.gain.setValueAtTime(0.0001, this.ctx.currentTime + inicio);
                ganho.gain.exponentialRampToValueAtTime(0.35, this.ctx.currentTime + inicio + 0.03);
                ganho.gain.exponentialRampToValueAtTime(0.0001, this.ctx.currentTime + inicio + duracao);
                osc.connect(ganho);
                ganho.connect(this.ctx.destination);
                osc.start(this.ctx.currentTime + inicio);
                osc.stop(this.ctx.currentTime + inicio + duracao + 0.05);
            };

            for (let i = 0; i < vezes; i++) {
                tom(880, i * 0.9, 0.45);
                tom(660, i * 0.9 + 0.4, 0.55);
            }
        },
    }));
});
