/**
 * Reconhecimento facial estilo catraca (face-api.js / open source).
 * A pessoa olha para a câmera; quando o rosto bate com o cadastro, libera.
 */
const MODEL_URL = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api@1.7.15/model';
const SCRIPT_URL = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api@1.7.15/dist/face-api.js';

let carregando = null;
let modelosProntos = false;

function carregarScript(src) {
    return new Promise((resolve, reject) => {
        if (document.querySelector(`script[src="${src}"]`)) {
            resolve();
            return;
        }
        const s = document.createElement('script');
        s.src = src;
        s.async = true;
        s.onload = () => resolve();
        s.onerror = () => reject(new Error('Falha ao carregar face-api.js'));
        document.head.appendChild(s);
    });
}

export async function garantirFaceApi() {
    if (modelosProntos && window.faceapi) {
        return window.faceapi;
    }
    if (carregando) {
        return carregando;
    }

    carregando = (async () => {
        await carregarScript(SCRIPT_URL);
        const faceapi = window.faceapi;
        if (! faceapi) {
            throw new Error('face-api não disponível');
        }
        await Promise.all([
            faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
            faceapi.nets.faceLandmark68TinyNet.loadFromUri(MODEL_URL),
            faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL),
        ]);
        modelosProntos = true;
        return faceapi;
    })();

    try {
        return await carregando;
    } finally {
        carregando = null;
    }
}

export async function descriptorDeUrl(url) {
    const faceapi = await garantirFaceApi();
    const img = await faceapi.fetchImage(url);
    const deteccao = await faceapi
        .detectSingleFace(img, new faceapi.TinyFaceDetectorOptions({ inputSize: 416, scoreThreshold: 0.4 }))
        .withFaceLandmarks(true)
        .withFaceDescriptor();

    if (! deteccao) {
        throw new Error('Não encontramos rosto na foto cadastrada.');
    }

    return deteccao.descriptor;
}

export async function descriptorDoVideo(videoEl) {
    const faceapi = await garantirFaceApi();
    if (! videoEl || videoEl.readyState < 2) {
        return null;
    }

    const deteccao = await faceapi
        .detectSingleFace(videoEl, new faceapi.TinyFaceDetectorOptions({ inputSize: 320, scoreThreshold: 0.45 }))
        .withFaceLandmarks(true)
        .withFaceDescriptor();

    return deteccao?.descriptor ?? null;
}

/** Distância euclidiana típica: < 0.5 = mesma pessoa (bem parecido), < 0.6 aceitável. */
export function distancia(a, b) {
    if (! a || ! b || a.length !== b.length) {
        return 99;
    }
    let soma = 0;
    for (let i = 0; i < a.length; i++) {
        const d = a[i] - b[i];
        soma += d * d;
    }
    return Math.sqrt(soma);
}

export function match(a, b, limiar = 0.55) {
    return distancia(a, b) <= limiar;
}

window.FilaPlayFacial = {
    garantirFaceApi,
    descriptorDeUrl,
    descriptorDoVideo,
    distancia,
    match,
};
