# 🏛️ Arquitectura de Base de Datos - ManaHoarder

## Entidades Principales

### 1️⃣ **User** (Usuarios del sistema)
**Función:** Gestión de cuentas y perfiles  
**Almacena:**
- `email` (único, para autenticación)
- `nickname` (apodo único del usuario)
- `password` (hasheada con bcrypt)
- `profilePictureFilename` (foto de perfil)
- `profileArtUrl` (URL de arte de fondo)
- `roles` (array: ROLE_USER, ROLE_ADMIN)
- `createdAt` (fecha de creación)

**Relaciones:**
- `OneToMany` con `Decklist` (un usuario tiene muchos mazos)
- `OneToMany` con `TournamentParticipant` (participación en torneos)
- `OneToMany` con `Tournament` (torneos creados como organizador)

---

### 2️⃣ **Decklist** (Mazos del usuario)
**Función:** Almacenar mazos de Magic: The Gathering  
**Almacena:**
- `name` (nombre del mazo)
- `format` (formato: Modern, Standard, Commander, etc.)
- `description` (descripción opcional)
- `user_id` (FK a User, creador del mazo)
- `createdAt` (fecha de creación)

**Relaciones:**
- `ManyToOne` con `User` (pertenece a un usuario)
- `OneToMany` con `TournamentParticipant` (puede usarse en torneos)

---

### 3️⃣ **Tournament** (Torneos)
**Función:** Crear y gestionar torneos de Magic  
**Almacena:**
- `name` (nombre del torneo)
- `description` (descripción opcional)
- `format` (formato del torneo)
- `state` (estado: pending, in_progress, completed)
- `maxPlayers` (número máximo de participantes)
- `creator_id` (FK a User, quien crea el torneo)
- `createdAt` y `updatedAt` (timestamps)

**Relaciones:**
- `OneToMany` con `TournamentParticipant` (lista de participantes)
- `ManyToOne` con `User` (creador del torneo)

---

### 4️⃣ **TournamentParticipant** (Participantes en torneos)
**Función:** Relación entre Usuarios y Torneos  
**Almacena:**
- `user_id` (FK a User)
- `tournament_id` (FK a Tournament)
- `decklist_id` (FK a Decklist, mazo utilizado)
- `playerStatus` (estado del jugador)
- `createdAt` (fecha de inscripción)

**Relaciones:**
- `ManyToOne` con `User`
- `ManyToOne` con `Tournament`
- `ManyToOne` con `Decklist`

---

### 5️⃣ **ScryfallCard** (Caché de datos de Scryfall)
**Función:** Cache perezosa de cartas desde la API de Scryfall  
**Almacena:**
- `scryfallId` (UUID único de Scryfall)
- `name` (nombre de la carta)
- `manaCost` (coste de maná)
- `imageUrl` (URL de la imagen)
- `type` (tipo de carta)
- `cardText` (texto de la carta nullable)
- `cardSet` (set al que pertenece)
- `createdAt` (timestamp de caché)

---

## 🗄️ Diagrama de Relaciones

```
User (1)
  ├─ (1:M) → Decklist
  │          └─ (1:M) → TournamentParticipant
  │                      └─ (M:1) → Tournament
  │
  ├─ (1:M) → TournamentParticipant (como jugador)
  │
  └─ (1:M) → Tournament (como creador)
```

---

## Integración con Scryfall API

La API se utiliza para:
- Búsqueda de cartas por nombre
- Filtrado por formato legal (`legal:modern`, `legal:standard`, etc.)
- Obtención de datos de cartas (costo de maná, tipo, texto, imagen)
- Validación de legalidad de cartas en formatos específicos

Los datos se cachean en `ScryfallCard` para optimizar consultas posteriores.

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
