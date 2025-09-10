\--- equitylink/backend/README.md ---

# Equity Link - Backend

**Framework:** Laravel 10
**Base de datos:** MySQL 8.0 (Docker)
**Gestión de permisos:** Spatie Laravel-Permission

---

## Requisitos

-   PHP ≥ 8.1
-   Composer
-   MySQL o Docker
-   Node.js (para Mix si es necesario)

---

## Instalación y ejecución

### 1. Usando Docker para MySQL

```bash
docker-compose up -d
```

### 2. Instalar dependencias de Laravel

```bash
composer install
cp .env.example .env
php artisan key:generate
```

### 3. Configurar base de datos

-   `.env` debe tener los datos de conexión a MySQL:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=equitylink
DB_USERNAME=equitylink_user
DB_PASSWORD=equitylink_pass
```

### 4. Migrar base de datos

```bash
php artisan migrate
```

### 5. Iniciar servidor

```bash
php artisan serve
```

-   La API estará disponible en `http://localhost:8000`.

---
