# Development Docker Setup

This directory contains Docker configuration for local development of the Penurwill Agent project on macOS.

## Prerequisites

- Docker Desktop installed and running
- macOS with Homebrew (optional, for convenience)

## Quick Start

### 1. Start MySQL Container

From the project root directory:

```bash
docker-compose -f docker/dev/docker-compose.yml up -d
```

This will start:
- **MySQL 8.0** on port `3306`
- **phpMyAdmin** on port `8080` (optional database browser)

### 2. Run Laravel Setup (One-time)

```bash
# Generate app key if not already set
php artisan key:generate

# Run database migrations
php artisan migrate

# Seed database (optional)
php artisan db:seed
```

### 3. Start Development Server

Run on your macBook (not inside container):

```bash
composer dev
```

Or run frontend and backend separately:

```bash
# Terminal 1: Backend
php artisan serve

# Terminal 2: Frontend
pnpm run dev

# Terminal 3: Queue (optional)
php artisan queue:listen

# Terminal 4: Logs (optional)
php artisan pail
```

## Database Credentials

- **Host**: `127.0.0.1` (from macBook)
- **Port**: `3306`
- **Database**: `penurwill`
- **Username**: `mysql`
- **Password**: `password`
- **Root Password**: `root`

## Accessing Services

### phpMyAdmin
- URL: `http://localhost:8080`
- Username: `mysql`
- Password: `password`

### Laravel Application
- URL: `http://localhost:5173` (Vite dev server)
- Backend: `http://localhost:8000` (if using `php artisan serve`)

## Container Management

### View Container Logs

```bash
# MySQL logs
docker logs penurwill-mysql

# phpMyAdmin logs
docker logs penurwill-phpmyadmin

# Follow logs in real-time
docker logs -f penurwill-mysql
```

### Stop Containers

```bash
docker-compose -f docker/dev/docker-compose.yml down
```

### Stop and Remove Data

```bash
docker-compose -f docker/dev/docker-compose.yml down -v
```

## Troubleshooting

### MySQL Connection Refused

Ensure Docker containers are running:
```bash
docker ps
```

If containers aren't running, start them:
```bash
docker-compose -f docker/dev/docker-compose.yml up -d
```

### Port Already in Use

If port 3306 is already in use, either:
1. Stop the service using that port
2. Change the port in `docker-compose.yml`:
   ```yaml
   ports:
     - "3307:3306"  # Change to 3307
   ```
   Then update `.env` to match:
   ```
   DB_PORT=3307
   ```

### Database Not Found

Run migrations after starting containers:
```bash
php artisan migrate
```

### Permission Denied Errors

Ensure Docker daemon is running and you have permissions:
```bash
docker version
```

## Development Workflow

1. **Start containers**: `docker-compose -f docker/dev/docker-compose.yml up -d`
2. **Run migrations** (if needed): `php artisan migrate`
3. **Start dev server**: `composer dev`
4. **Develop** your features
5. **Stop containers**: `docker-compose -f docker/dev/docker-compose.yml down`

## Notes

- MySQL data persists in Docker volume `mysql_data` even after stopping containers
- The `.env` file is configured for this Docker setup
- PHP and Node.js run on your macBook, not in containers (more efficient for development)
- For production, consider using Docker for entire application stack
