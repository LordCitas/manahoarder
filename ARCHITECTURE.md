# 🏛️ Arquitectura de Base de Datos - ManaHoarder

## Estrategia: Base de Datos + Caché Perezosa (Lazy Cache)

### Problema Original
❌ **LocalStorage no es viable porque:**
- Límite de 5MB por navegador
- Se pierde si el usuario borra cookies/historial
- No funciona cross-device (móvil, tablet, PC)
- No hay estadísticas globales

---

## ✅ Solución Implementada

### 1️⃣ **Tabla `ScryfallCard`** (Mini-espejo de Scryfall)
**Función:** Cache de datos de cartas desde Scryfall  
**Almacena:**
- `scryfallId` (UUID único de Scryfall)
- `name` (nombre de la carta)
- `manaCost` (coste de maná)
- `imageUrl` (URL de la imagen)
- `type` (tipo de carta: Creature, Sorcery, etc.)
- `cardText` (texto de la carta nullable)
- `cardSet` (set al que pertenece)
- `createdAt` (timestamp de cuándo se cacheó)

**Tamaño esperado:** ~500 bytes por carta cacheada  
**Ventaja:** SQLite ocupa muy poco espacio

---

### 2️⃣ **Tabla `UserCard`** (Colección del usuario)
**Función:** Relación entre Usuario → Carta cacheada  
**Almacena:**
- `user_id` (FK a User)
- `scryfallCard_id` (FK a ScryfallCard)
- `quantity` (cantidad de copias: 1, 2, 3, 4)
- `isFoil` (¿está brillante?)
- `dateAdded` (cuándo el usuario la agregó)
- `album_id` (a qué álbum pertenece)

**Relaciones:**
- `ManyToOne` con `User` (un usuario tiene muchas UserCard)
- `ManyToOne` con `ScryfallCard` (muchos usuarios pueden tener la misma carta)
- `ManyToOne` con `Album` (las cartas se organizan en álbumes)

---

### 3️⃣ **Tabla `Album`** (Organización de colecciones)
**Función:** Agrupar cartas del usuario por tema/propósito  
**Almacena:**
- `title` (nombre del álbum: "Mi Mazo Rojo", "Cartas de Coleccionista")
- `description` (descripción opcional)
- `user_id` (FK a User)
- `createdAt` (cuándo se creó)

**Ejemplo de uso:**
```
Usuario → [Álbum "Mazo Estándar"] → [UserCard (Goblin, qty=2, foil=true)]
                                  → [UserCard (Lava Spike, qty=3, foil=false)]
                                  → ...

Usuario → [Álbum "Cartas Raras"] → [UserCard (Black Lotus, qty=1, foil=true)]
```

---

## 🔄 Flujo de Funcionamiento

### Escenario: Usuario abre su álbum "Mazo Rojo"

```
┌─ Usuario abre álbum "Mazo Rojo"
│
├─ Backend busca: SELECT * FROM user_card WHERE album_id = ? AND user_id = ?
│
├─ Para cada UserCard, obtiene la carta con:
│  SELECT * FROM scryfall_card WHERE id = scryfallCard_id
│
├─ ¿La carta está en ScryfallCard?
│  ├─ SÍ  → Devuelve instantáneamente (base de datos local)
│  └─ NO  → Llama API Scryfall → Cachea en ScryfallCard
│
└─ Frontend renderiza las 100 cartas en < 200ms
```

### Ventajas:

✅ **Velocidad:** La 2ª vez que alguien abre una carta, es instantáneo  
✅ **Rate Limit:** No saturamos la API de Scryfall  
✅ **Multi-dispositivo:** El usuario ve su colección igual en PC, móvil, tablet  
✅ **Estadísticas:** Podemos hacer queries globales (carta más usada, etc.)  
✅ **Espacio mínimo:** SQLite ocupa < 10MB incluso con 5000 cartas  
✅ **Offline-ready:** La caché permite ver cartas sin conexión

---

## 🗄️ Diagrama de Relaciones

```
User (1)
  ├─ (1:M) → UserCard
  │          ├─ (M:1) → ScryfallCard (caché Scryfall)
  │          └─ (M:1) → Album
  │
  └─ (1:M) → Album
```

---

## 📊 Ejemplos de Queries Útiles

### Obtener todas las cartas de un álbum del usuario

```php
$album->getUserCards(); // Lazy load desde Doctrine
// SELECT * FROM user_card WHERE album_id = ? AND user_id = ?
```

### Obtener datos de la carta (nombre, imagen, etc.)

```php
foreach ($userCards as $userCard) {
    $card = $userCard->getScryfallCard(); // ManyToOne relación
    echo $card->getName(); // "Black Lotus"
    echo $card->getImageUrl(); // URL de Scryfall
}
```

### Encontrar la carta más integrada en los álbumes del usuario

```php
SELECT sc.name, COUNT(uc.id) as times_used
FROM scryfall_card sc
JOIN user_card uc ON sc.id = uc.scryfall_card_id
WHERE uc.user_id = ?
GROUP BY sc.id
ORDER BY times_used DESC
LIMIT 10;
```

### Calcular valor total de colección (si almacenamos precios)

```php
SELECT SUM(sc.price * uc.quantity) as total_value
FROM scryfall_card sc
JOIN user_card uc ON sc.id = uc.scryfall_card_id
WHERE uc.user_id = ?;
```

---

## 🚀 Próximas Optimizaciones

1. **Caché Redis en memoria** para búsquedas frecuentes
2. **Sincronización automática** de precios desde Scryfall cada X tiempo
3. **Búsqueda full-text** en nombre de cartas cacheadas
4. **Estadísticas de colección** en dashboard
5. **Export a CSV/PDF** de colecciones

---

## 💡 Conclusión

Esta arquitectura respeta el rate limit de Scryfall, ocupa mínimo espacio, y permite una experiencia multi-dispositivo consistente. El usuario nunca pierde su colección porque **vive en el servidor**, no en el navegador.
