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
    
    if (layer1 && layer2) {
        try {
            // Leemos el atributo de Twig
            const rawData = layer1.getAttribute('data-backgrounds');
            console.log("Datos brutos recibidos de Twig:", rawData); // <-- MENSAJE DE CONTROL 1

            const images = JSON.parse(rawData || "[]");
            console.log("Array de imágenes procesado por JS:", images); // <-- MENSAJE DE CONTROL 2

            if (images.length === 0) {
                console.warn("Alerta: El array de imágenes está vacío.");
                return;
            }

            let currentIndex = 0;
            let isLayer1Active = true;

            // Forzamos la carga del primer fondo de inmediato
            console.log("Cargando primer fondo:", images[currentIndex]);
            layer1.style.backgroundImage = `url('${images[currentIndex]}')`;
            layer1.classList.add('active');

            // Función de intercambio
            const changeBackground = () => {
                currentIndex = (currentIndex + 1) % images.length;
                const nextImageUrl = images[currentIndex];
                console.log("Cambiando fondo al índice:", currentIndex, nextImageUrl);

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

            // Ejecutar cada 8 segundos
            setInterval(changeBackground, 8000);

        } catch (error) {
            console.error("Error crítico procesando los fondos animados:", error);
        }
    } else {
        console.error("No se encontraron las capas de fondo (#bg-layer-1 o #bg-layer-2) en el HTML.");
    }
});