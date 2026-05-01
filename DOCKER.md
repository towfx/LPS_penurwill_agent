# Docker Setup Guide

This document describes the Docker setup for the Penurwill Agent application, including both development and production configurations.

## Overview

- **Development**: Lightweight setup with MySQL for local development on macOS (`docker/dev/docker-compose.yml`)
- **Production**: Full stack setup with Nginx, PHP-FPM, MySQL, and Redis (`docker-compose.prod.yml`)

## Development Setup (macOS)

### Quick Start

```bash
# Start MySQL container
docker-compose -f docker/dev/docker-compose.yml up -d

# Generate app key and run migrations
php artisan key:generate
php artisan migrate

# Run development server on your macBook
composer dev
```

See `docker/dev/README.md` for detailed development documentation.

## Production Setup

### Prerequisites

- Docker and Docker Compose installed
- Environment variables configured (see `.env.example`)
- SSL certificates ready (or use self-signed for testing)

### Environment Configuration

Create `.env` with production settings:

```bash
cp .env.example .env
```

Key variables for production:
```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=penurwill
DB_USERNAME=mysql
DB_PASSWORD=your_secure_password

REDIS_HOST=redis
REDIS_PASSWORD=your_redis_password
```

### Build and Start

```bash
# Build the application Docker image
docker build -t penurwill:latest .

# Start all services
docker-compose -f docker-compose.prod.yml up -d

# Run migrations
docker-compose -f docker-compose.prod.yml exec app php artisan migrate --force

# Create an admin user
docker-compose -f docker-compose.prod.yml exec app php artisan tinker
```

### Services

#### App (PHP-FPM)
- Runs the Laravel application
- Automatically runs migrations on startup
- Cache optimization enabled
- Health checks every 10 seconds

#### Nginx
- Reverse proxy and web server
- SSL/TLS termination
- Gzip compression
- Static file caching
- Security headers
- Redirects HTTP to HTTPS

#### MySQL
- Database server
- Persistent volume storage
- Health checks
- Performance optimized configuration

#### Redis (Optional)
- Cache and session storage
- Message queue support
- High-performance data store

### SSL/TLS Certificates

For production, place certificates in `docker/ssl/`:

```bash
mkdir -p docker/ssl
cp /path/to/cert.pem docker/ssl/
cp /path/to/key.pem docker/ssl/
```

For testing with self-signed certificates:

```bash
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout docker/ssl/key.pem \
  -out docker/ssl/cert.pem
```

### Docker Compose Commands

```bash
# Start services in background
docker-compose -f docker-compose.prod.yml up -d

# Stop services
docker-compose -f docker-compose.prod.yml down

# Stop and remove volumes
docker-compose -f docker-compose.prod.yml down -v

# View logs
docker-compose -f docker-compose.prod.yml logs -f app

# Run artisan commands
docker-compose -f docker-compose.prod.yml exec app php artisan <command>

# Access MySQL
docker-compose -f docker-compose.prod.yml exec mysql mysql -u mysql -p penurwill

# Access Redis CLI
docker-compose -f docker-compose.prod.yml exec redis redis-cli
```

## Image Structure

### Multi-stage Build

The Dockerfile uses three stages for optimization:

1. **Frontend Builder**: Node.js 20 - builds Vue/Vite assets
2. **PHP Builder**: PHP 8.2 - installs Composer dependencies
3. **Production**: PHP 8.2 - final optimized image with only production code

### Image Size Optimization

- Alpine Linux base (small footprint)
- Multi-stage builds (no build tools in final image)
- Only production dependencies included
- Vendor directory cached for faster builds

## Performance Optimization

### PHP-FPM Configuration
- Dynamic process manager
- Max 20 child processes
- Min 5, max 15 spare servers
- 1000 requests per worker

### Nginx Configuration
- Gzip compression for text assets
- 1-year cache for static files
- Session affinity for reliability
- HTTP/2 support

### MySQL Configuration
- InnoDB buffer pool optimization
- Max connections: 1000
- Max packet size: 256MB
- UTF-8MB4 character set

### Redis Configuration
- Persistent storage
- Health checks enabled
- Automatic restart on failure

## Troubleshooting

### Application Won't Start

Check logs:
```bash
docker-compose -f docker-compose.prod.yml logs app
```

Common issues:
- Database not ready: Wait a few seconds for MySQL health check
- Migration failures: Check database credentials in `.env`
- Permission errors: Ensure proper file permissions

### Database Connection Failed

```bash
# Check MySQL is running
docker-compose -f docker-compose.prod.yml ps mysql

# Check MySQL logs
docker-compose -f docker-compose.prod.yml logs mysql

# Test connection
docker-compose -f docker-compose.prod.yml exec mysql \
  mysql -h mysql -u mysql -p -e "SELECT 1"
```

### Nginx Returns 502 Bad Gateway

- PHP-FPM may not be responding
- Check app logs: `docker-compose -f docker-compose.prod.yml logs app`
- Restart app service: `docker-compose -f docker-compose.prod.yml restart app`

### High Memory Usage

- Adjust PHP-FPM `pm.max_children` in `docker/www.conf`
- Increase MySQL buffer pool in `docker/mysql-init.sql`
- Check application logs for memory leaks

## Health Checks

Each service has health checks configured:

- **App**: HTTP check on PHP-FPM ping endpoint
- **MySQL**: mysqladmin ping
- **Nginx**: HTTP GET to `/health` endpoint
- **Redis**: redis-cli PING

Monitor health:
```bash
docker-compose -f docker-compose.prod.yml ps
```

## Security Best Practices

1. **Change default passwords** in `.env`
2. **Use strong SSL certificates** from a trusted CA
3. **Enable HTTPS only** (HTTP redirects to HTTPS)
4. **Restrict database access** to app container only
5. **Keep images updated**: `docker-compose -f docker-compose.prod.yml pull`
6. **Scan images for vulnerabilities**: `docker scan penurwill:latest`
7. **Use secrets management** for sensitive data in production
8. **Disable debugging** (APP_DEBUG=false)

## Scaling

### Horizontal Scaling

For multiple app instances behind a load balancer:

```yaml
app:
  deploy:
    replicas: 3
```

Then use a load balancer (HAProxy, AWS ALB, etc.) in front of Nginx.

### Database Scaling

For read replicas:
1. Set up MySQL replication (see `docker/mysql-init.sql`)
2. Configure read connection in `config/database.php`
3. Use write connection for mutations, read connection for queries

## Maintenance

### Backup Database

```bash
docker-compose -f docker-compose.prod.yml exec mysql \
  mysqldump -u mysql -p penurwill > backup.sql
```

### Restore Database

```bash
docker-compose -f docker-compose.prod.yml exec -T mysql \
  mysql -u mysql -p penurwill < backup.sql
```

### Update Application

```bash
# Build new image
docker build -t penurwill:v2.0 .

# Stop current services
docker-compose -f docker-compose.prod.yml down

# Update docker-compose to use new tag, then start
docker-compose -f docker-compose.prod.yml up -d
```

### Clear Application Cache

```bash
docker-compose -f docker-compose.prod.yml exec app php artisan cache:clear
docker-compose -f docker-compose.prod.yml exec app php artisan route:cache
```

## Related Files

- **Dockerfile**: Multi-stage build for production image
- **docker-compose.prod.yml**: Production service configuration
- **docker/dev/docker-compose.yml**: Development MySQL setup
- **docker/php.ini**: PHP configuration
- **docker/www.conf**: PHP-FPM configuration
- **docker/nginx.conf**: Nginx main configuration
- **docker/conf.d/default.conf**: Nginx site configuration
- **docker/entrypoint.sh**: Container startup script
- **.dockerignore**: Files excluded from Docker build

## Additional Resources

- [Docker Documentation](https://docs.docker.com/)
- [Docker Compose Documentation](https://docs.docker.com/compose/)
- [Laravel Docker Guide](https://laravel.com/docs/12.x/deployment#docker)
- [PHP-FPM Documentation](https://www.php.net/manual/en/install.fpm.php)
- [Nginx Documentation](https://nginx.org/en/docs/)
