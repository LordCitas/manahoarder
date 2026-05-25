/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.css';

console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');

// assets/app.js

document.addEventListener('DOMContentLoaded', () => {
    // Definimos el listado con los nombres de tus imágenes locales procesadas por AssetMapper
    const images = [
        '/assets/images/blanco.png',
        '/assets/images/azul.png',
        '/assets/images/negro.png',
        '/assets/images/rojo.png',
        '/assets/images/verde.png',
        '/assets/images/incoloro.png'
    ];

    let currentIndex = 0;
    const layer1 = document.getElementById('bg-layer-1');
    const layer2 = document.getElementById('bg-layer-2');
    
    let isLayer1Active = true;

    // Inicializamos la primera imagen en la capa 1
    if (layer1 && layer2) {
        layer1.style.backgroundImage = `url('${images[currentIndex]}')`;
        layer1.classList.add('active');

        // Función que realiza el intercambio suave
        const changeBackground = () => {
            // Avanzamos al siguiente índice de la secuencia de forma circular (del 0 al 5)
            currentIndex = (currentIndex + 1) % images.length;
            const nextImageUrl = images[currentIndex];

            if (isLayer1Active) {
                // Si la capa 1 está visible, cargamos la nueva foto en la capa 2 y hacemos el fundido
                layer2.style.backgroundImage = `url('${nextImageUrl}')`;
                layer2.classList.add('active');
                layer1.classList.remove('active');
            } else {
                // Si la capa 2 está visible, cargamos la nueva foto en la capa 1 y hacemos el fundido
                layer1.style.backgroundImage = `url('${nextImageUrl}')`;
                layer1.classList.add('active');
                layer2.classList.remove('active');
            }

            // Invertimos el interruptor de la capa activa
            isLayer1Active = !isLayer1Active;
        };

        // Cada cuánto tiempo cambia la imagen (ej: 8000 milisegundos = 8 segundos)
        // Como en el CSS pusimos que la transición dura 3 segundos, la imagen estará fija 5 segundos y se fundirá durante 3.
        setInterval(changeBackground, 8000);
    }
});
