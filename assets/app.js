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
    const layer1 = document.getElementById('bg-layer-1');
    const layer2 = document.getElementById('bg-layer-2');
    
    // Verificamos que las capas existan en la página actual
    if (layer1 && layer2) {
        // Leemos el atributo data-backgrounds que Twig rellenó y lo convertimos en un Array de JS
        const images = JSON.parse(layer1.dataset.backgrounds || "[]");

        if (images.length === 0) return; // Si no hay imágenes, detenemos el script

        let currentIndex = 0;
        let isLayer1Active = true;

        // Inicializamos la primera imagen en la capa 1 usando la ruta real de Symfony
        layer1.style.backgroundImage = `url('${images[currentIndex]}')`;
        layer1.classList.add('active');

        // Función que realiza el intercambio suave
        const changeBackground = () => {
            currentIndex = (currentIndex + 1) % images.length;
            const nextImageUrl = images[currentIndex];

            if (isLayer1Active) {
                layer2.style.backgroundImage = `url('${nextImageUrl}')`;
                layer2.classList.add('active');
                layer1.classList.remove('active');
            } else {
                layer1.style.backgroundImage = `url('${nextImageUrl}')`;
                layer1.classList.add('active');
                layer2.classList.remove('active');
            }

            isLayer1Active = !isLayer1Active;
        };

        // Cambiar de imagen cada 8 segundos
        setInterval(changeBackground, 8000);
    }
});
