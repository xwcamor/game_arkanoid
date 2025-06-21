const canvas = document.getElementById('lienzoJuego');
const ctx = canvas.getContext('2d');

// Dimensiones del canvas (coinciden con el HTML)
const ANCHO_CANVAS = canvas.width;
const ALTO_CANVAS = canvas.height;

// Elementos del juego (valores de ejemplo, se definirán mejor después)
let pala = {
    x: ANCHO_CANVAS / 2 - 50, // Centrada inicialmente
    y: ALTO_CANVAS - 30,
    ancho: 100,
    alto: 15,
    color: '#0095DD',
    velocidad: 8
};

let bola = {
    x: ANCHO_CANVAS / 2,
    y: ALTO_CANVAS - 50,
    radio: 10,
    color: '#0095DD',
    velocidadX: 4,
    velocidadY: -4 // Hacia arriba inicialmente
};

// Ladrillos (se cargarán por nivel)
let ladrillos = [];
const FILAS_LADRILLOS = 5;
const COLUMNAS_LADRILLOS = 8;
const ANCHO_LADRILLO = 75;
const ALTO_LADRILLO = 20;
const PADDING_LADRILLO = 10;
const OFFSET_SUPERIOR_LADRILLO = 30;
const OFFSET_IZQUIERDO_LADRILLO = 30;
const COLOR_LADRILLO = '#D9534F';

// Controles del juego
let teclaDerechaPresionada = false;
let teclaIzquierdaPresionada = false;

// Información del juego
let puntuacion = 0;
let vidas = 3;
let nivelActual = 1;
const MAX_NIVELES = 3;

// Elementos del DOM para actualizar la info
const spanPuntuacion = document.getElementById('puntuacion');
const spanVidas = document.getElementById('vidas');
const spanNivelActual = document.getElementById('nivelActual');
const divMensajesJuego = document.getElementById('mensajesJuego');

// Referencias a los botones
const btnIniciarJuego = document.getElementById('btnIniciarJuego');
const btnReiniciarNivel = document.getElementById('btnReiniciarNivel');
const btnReiniciarJuego = document.getElementById('btnReiniciarJuego');

let juegoComenzado = false; // Para controlar si el juego ya se ha iniciado una vez
let juegoPausado = true; // El juego empieza pausado, esperando a "Iniciar Juego"
let gameLoopId = null;

// --- Música específica del Juego ---
const musicaJuego = new Audio('sonidos/musica_juego.mp3');
musicaJuego.loop = true;
musicaJuego.volume = 0.4; // Volumen para la música del juego
let musicaJuegoDebeSonarLocalmente = false;
let musicaJuegoRealmenteIniciada = false;

function controlarMusicaJuego() {
    console.log(`[MusicaDebug] controlarMusicaJuego: debeSonarLocalmente=${musicaJuegoDebeSonarLocalmente}, window.musicaMuteadaUsuario=${window.musicaMuteadaUsuario}, realmenteIniciada=${musicaJuegoRealmenteIniciada}, musicaJuego.paused=${musicaJuego.paused}`);
    if (musicaJuegoDebeSonarLocalmente && !window.musicaMuteadaUsuario) {
        if (!musicaJuegoRealmenteIniciada || musicaJuego.paused) {
            console.log("[MusicaDebug] Intentando reproducir música del juego...");
            musicaJuego.play().then(() => {
                console.log("[MusicaDebug] Música del juego reproduciéndose.");
                musicaJuegoRealmenteIniciada = true;
            }).catch(error => {
                console.warn("[MusicaDebug] Música del juego bloqueada o error al reproducir:", error);
            });
        } else {
            console.log("[MusicaDebug] Música del juego ya sonando (no pausada y ya iniciada).");
        }
    } else {
        console.log("[MusicaDebug] Pausando música del juego o no debe sonar.");
        musicaJuego.pause();
    }
}

// Funciones globales para ser llamadas por el botón de silencio de index.php
window.pausarMusicaJuego = function() {
    musicaJuego.pause();
};

window.reanudarMusicaJuegoSiDebeSonarLocalmente = function() {
    // Esta función es llamada cuando el usuario desmutea globalmente.
    // Solo reanudamos si el juego internamente 'quiere' que la música suene.
    if (musicaJuegoDebeSonarLocalmente && !window.musicaMuteadaUsuario) {
        controlarMusicaJuego(); // Re-evalúa y reproduce si es necesario
    }
};

// --- Event Listeners para el movimiento de la pala ---
document.addEventListener('keydown', alPresionarTecla);
document.addEventListener('keyup', alSoltarTecla);
document.addEventListener("mousemove", movimientoRaton, false);

// --- Event Listeners para los botones ---
btnIniciarJuego.addEventListener('click', () => {
    // Solo actuar si el juego está en un estado donde se puede iniciar/reanudar.
    // Esto generalmente significa que juegoPausado es true, o que juegoComenzado es false.
    if (juegoPausado || !juegoComenzado) {
        if (typeof gestionarMusicaLogin === 'function') { // Detener música de login si existe
            gestionarMusicaLogin(false);
        }
        musicaJuegoDebeSonarLocalmente = true;
        controlarMusicaJuego();

        if (!juegoComenzado) {
            // Primera vez que se inicia el juego o después de "Jugar de Nuevo"
            vidas = 3;
            puntuacion = 0;
            nivelActual = 1; // Asegurar que empezamos por el nivel 1
            cargarNivel(nivelActual); // Esto configura el nivel, resetea bola/pala Y PONE juegoPausado = true
            juegoComenzado = true;
            // cargarNivel() habrá puesto un mensaje y dejado juegoPausado = true.
            // El flujo continuará para despausar y empezar el bucle.
        }

        // Para todos los casos (primer inicio, reanudar tras pausa, reanudar tras cargarNivel por !juegoComenzado):
        juegoPausado = false; // Asegurar que está despausado antes de empezar el bucle
        empezarBucleJuego();
        divMensajesJuego.textContent = ''; // Limpiar mensajes como "Presiona Iniciar" o los de cargarNivel
        btnIniciarJuego.textContent = "Reanudar Juego"; // Actualizar texto del botón
    }
});

btnReiniciarNivel.addEventListener('click', () => {
    if (!juegoComenzado || vidas <= 0) return;
    reiniciarBolaYPala();
    actualizarInfoJuego();
    if (juegoPausado) {
        juegoPausado = false;
        empezarBucleJuego();
    }
    musicaJuegoDebeSonarLocalmente = true;
    controlarMusicaJuego();
    divMensajesJuego.textContent = 'Nivel Reiniciado';
    setTimeout(() => divMensajesJuego.textContent = '', 1500);
});

btnReiniciarJuego.addEventListener('click', () => {
    detenerBucleJuego();
    
    juegoComenzado = false;
    juegoPausado = true;
    vidas = 3;
    puntuacion = 0;
    cargarNivel(1);
    actualizarInfoJuego();
    divMensajesJuego.textContent = 'Juego Reiniciado. Presiona "Iniciar Juego".';
    btnIniciarJuego.textContent = "Iniciar Juego";
});

function alPresionarTecla(e) {
    if (e.key === 'Right' || e.key === 'ArrowRight') {
        teclaDerechaPresionada = true;
    } else if (e.key === 'Left' || e.key === 'ArrowLeft') {
        teclaIzquierdaPresionada = true;
    }
}

function alSoltarTecla(e) {
    if (e.key === 'Right' || e.key === 'ArrowRight') {
        teclaDerechaPresionada = false;
    } else if (e.key === 'Left' || e.key === 'ArrowLeft') {
        teclaIzquierdaPresionada = false;
    }
}

function movimientoRaton(e) {
    const posicionRelativaX = e.clientX - canvas.offsetLeft;
    if (posicionRelativaX > 0 && posicionRelativaX < ANCHO_CANVAS) {
        pala.x = posicionRelativaX - pala.ancho / 2;
        // Asegurar que la pala no se salga por los lados
        if (pala.x < 0) {
            pala.x = 0;
        }
        if (pala.x + pala.ancho > ANCHO_CANVAS) {
            pala.x = ANCHO_CANVAS - pala.ancho;
        }
    }

    // Reanudar el juego si está pausado después de perder una vida y se mueve el ratón
    if (juegoPausado && juegoComenzado && vidas > 0 && divMensajesJuego.textContent.startsWith('¡Vida perdida!')) {
        juegoPausado = false;
        empezarBucleJuego();
        divMensajesJuego.textContent = ''; // Limpiar mensaje de "vida perdida"
        // El texto del btnIniciarJuego ya debería ser "Reanudar Juego"
    }
}

// --- Funciones de Dibujo ---
function dibujarPala() {
    ctx.beginPath();
    ctx.rect(pala.x, pala.y, pala.ancho, pala.alto);
    ctx.fillStyle = pala.color;
    ctx.fill();
    ctx.closePath();
}

function dibujarBola() {
    ctx.beginPath();
    ctx.arc(bola.x, bola.y, bola.radio, 0, Math.PI * 2);
    ctx.fillStyle = bola.color;
    ctx.fill();
    ctx.closePath();
}

function dibujarLadrillos() {
    ladrillos.forEach(columna => {
        columna.forEach(ladrillo => {
            if (ladrillo.visible) {
                ctx.beginPath();
                ctx.rect(ladrillo.x, ladrillo.y, ANCHO_LADRILLO, ALTO_LADRILLO);
                ctx.fillStyle = COLOR_LADRILLO;
                ctx.fill();
                ctx.closePath();
            }
        });
    });
}

// --- Lógica de Colisiones ---
function detectarColisiones() {
    if (juegoPausado) return;

    // Colisión con ladrillos
    for (let c = 0; c < ladrillos.length; c++) { 
        for (let r = 0; r < ladrillos[c].length; r++) {
            const ladrillo = ladrillos[c][r];
            if (ladrillo && ladrillo.visible) { 
                if (
                    bola.x + bola.radio > ladrillo.x &&
                    bola.x - bola.radio < ladrillo.x + ANCHO_LADRILLO &&
                    bola.y + bola.radio > ladrillo.y &&
                    bola.y - bola.radio < ladrillo.y + ALTO_LADRILLO
                ) {
                    bola.velocidadY = -bola.velocidadY;
                    ladrillo.visible = false;
                    puntuacion++;
                    actualizarInfoJuego();

                    if (todosLadrillosDestruidos()) {
                        pasarDeNivel();
                        return; // Salir de detectarColisiones ya que el nivel ha cambiado
                    }
                }
            }
        }
    }

    // Colisión con la pala
    if (
        bola.x + bola.radio > pala.x &&
        bola.x - bola.radio < pala.x + pala.ancho &&
        bola.y + bola.radio > pala.y &&
        bola.y - bola.radio < pala.y + pala.alto 
    ) {
        // Ajustar el ángulo de rebote basado en dónde golpea la pala
        let puntoGolpe = (bola.x - (pala.x + pala.ancho / 2)) / (pala.ancho / 2);
        let anguloRebote = puntoGolpe * (Math.PI / 3); // Max 60 grados

        // Mantener una velocidad vertical mínima para evitar que la bola se quede horizontal
        const velocidadTotal = Math.sqrt(Math.pow(bola.velocidadX, 2) + Math.pow(bola.velocidadY, 2));
        bola.velocidadX = velocidadTotal * Math.sin(anguloRebote);
        // Siempre rebotar hacia arriba
        bola.velocidadY = -Math.abs(velocidadTotal * Math.cos(anguloRebote));

        // Asegurar que velocidadY no sea demasiado plana
         if (Math.abs(bola.velocidadY) < 2) {
            bola.velocidadY = bola.velocidadY < 0 ? -2 : 2;
        }
    }

    // Colisión con paredes laterales
    if (bola.x + bola.velocidadX > ANCHO_CANVAS - bola.radio || bola.x + bola.velocidadX < bola.radio) {
        bola.velocidadX = -bola.velocidadX;
    }

    // Colisión con pared superior
    if (bola.y + bola.velocidadY < bola.radio) {
        bola.velocidadY = -bola.velocidadY;
    } else if (bola.y + bola.velocidadY > ALTO_CANVAS - bola.radio) {
        vidas--;
        actualizarInfoJuego();
        detenerBucleJuego();
        juegoPausado = true;
        if (vidas <= 0) {
            gameOver("¡Has perdido todas tus vidas!");
        } else {
            reiniciarBolaYPala();
            divMensajesJuego.textContent = '¡Vida perdida! Presiona "Reanudar Juego" o mueve el ratón para continuar.';
        }
    }
}

// --- Funciones de Estado del Juego ---
function reiniciarBolaYPala() {
    bola.x = ANCHO_CANVAS / 2;
    bola.y = ALTO_CANVAS - 50;
    bola.velocidadX = (Math.random() < 0.5 ? 1 : -1) * 4;
    bola.velocidadY = -4;
    pala.x = (ANCHO_CANVAS - pala.ancho) / 2;
}

function inicializarLadrillos(nivel) {
    ladrillos = [];
    let columnasParaNivel = COLUMNAS_LADRILLOS;
    let filasParaNivel = FILAS_LADRILLOS;

    // Ejemplo simple de variación por nivel
    if (nivel === 2) {
        columnasParaNivel = 6;
        filasParaNivel = 4;
    } else if (nivel === 3) {
        columnasParaNivel = 10;
        filasParaNivel = 6;
    }

    for (let c = 0; c < columnasParaNivel; c++) {
        ladrillos[c] = [];
        for (let r = 0; r < filasParaNivel; r++) {
            const ladrilloX = (c * (ANCHO_LADRILLO + PADDING_LADRILLO)) + OFFSET_IZQUIERDO_LADRILLO +
                              (ANCHO_CANVAS - (columnasParaNivel * (ANCHO_LADRILLO + PADDING_LADRILLO) - PADDING_LADRILLO)) / 2; // Centrar bloque de ladrillos
            const ladrilloY = (r * (ALTO_LADRILLO + PADDING_LADRILLO)) + OFFSET_SUPERIOR_LADRILLO;
            ladrillos[c][r] = { x: ladrilloX, y: ladrilloY, visible: true };
        }
    }
}

function cargarNivel(numNivel) {
    detenerBucleJuego();
    nivelActual = numNivel;
    inicializarLadrillos(numNivel);
    reiniciarBolaYPala();
    actualizarInfoJuego();
    divMensajesJuego.textContent = `Nivel ${nivelActual} cargado. Presiona "${btnIniciarJuego.textContent}"`;
    juegoPausado = true;
}

function todosLadrillosDestruidos() {
    for (let c = 0; c < ladrillos.length; c++) {
        for (let r = 0; r < ladrillos[c].length; r++) {
            if (ladrillos[c][r].visible) {
                return false;
            }
        }
    }
    return true;
}

function pasarDeNivel() {
    detenerBucleJuego();
    juegoPausado = true;
    // La música del juego debe continuar o reanudarse al pasar de nivel
    musicaJuegoDebeSonarLocalmente = true;
    controlarMusicaJuego();

    if (nivelActual < MAX_NIVELES) {
        nivelActual++;
        divMensajesJuego.textContent = `¡Nivel ${nivelActual -1} completado! Cargando Nivel ${nivelActual}...`;
        setTimeout(() => {
            cargarNivel(nivelActual); // Esto establece juegoPausado = true y muestra un mensaje
            // Reanudar automáticamente el juego:
            juegoPausado = false;
            empezarBucleJuego();
            // Limpiar mensaje de "Nivel X cargado..." para evitar confusión.
            divMensajesJuego.textContent = ''; 
        }, 2000);
    } else {
        gameOver("¡Felicidades! ¡Has completado todos los niveles!");
    }
}

function gameOver(mensaje) {
    detenerBucleJuego();
    
    divMensajesJuego.textContent = `GAME OVER: ${mensaje}`;
    juegoComenzado = false;
    juegoPausado = true;
    btnIniciarJuego.textContent = "Jugar de Nuevo";
}

function actualizarInfoJuego() {
    spanPuntuacion.textContent = puntuacion;
    spanVidas.textContent = vidas;
    spanNivelActual.textContent = nivelActual;
}

// --- Bucle Principal del Juego ---
function empezarBucleJuego() {
    if (juegoPausado) return;
    if (!gameLoopId) {
        gameLoopId = requestAnimationFrame(bucleJuego);
    }
}

function detenerBucleJuego() {
    if (gameLoopId) {
        cancelAnimationFrame(gameLoopId);
        gameLoopId = null;
    }
    juegoPausado = true;
}

function bucleJuego() {
    console.log("[BucleDebug] Entrando a bucleJuego. juegoPausado = " + juegoPausado);
    if (juegoPausado) {
        detenerBucleJuego(); // Asegura que se detiene si entra aquí.
        return;
    }

    ctx.clearRect(0, 0, ANCHO_CANVAS, ALTO_CANVAS);
    dibujarLadrillos();
    dibujarPala();
    dibujarBola();

    if (teclaDerechaPresionada && pala.x < ANCHO_CANVAS - pala.ancho) {
        pala.x += pala.velocidad;
    } else if (teclaIzquierdaPresionada && pala.x > 0) {
        pala.x -= pala.velocidad;
    }

    bola.x += bola.velocidadX;
    bola.y += bola.velocidadY;
    detectarColisiones();

    if (!juegoPausado) {
        gameLoopId = requestAnimationFrame(bucleJuego);
    } else {
        detenerBucleJuego();
    }
}

// --- Inicialización del juego ---
function configurarJuegoInicial() {
    actualizarInfoJuego();
    divMensajesJuego.textContent = '¡Bienvenido! Presiona "Iniciar Juego" para comenzar.';
}

document.addEventListener('DOMContentLoaded', (event) => {
    configurarJuegoInicial(); 
});

/*
    NOTA: Mejoras pendientes:
    - Diseños de niveles más variados y complejos (usar la variable 'nivel' en inicializarLadrillos).
    - Power-ups.
    - Mejoras visuales adicionales y "ambiente" neón.
    - Sonidos.
    - Sistema de pausa explícito (botón Pausa/Reanudar).
    - Guardado de puntuaciones (requiere más PHP y AJAX o recarga de página).
*/ 