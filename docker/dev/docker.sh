#!/bin/bash

# Docker helper script for Penurwill development
# Usage: source docker/dev/docker.sh
# Then use: docker-up, docker-down, docker-logs, etc.

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
COMPOSE_FILE="docker/dev/docker-compose.yml"

# Start containers
docker-up() {
    echo "🚀 Starting Docker containers..."
    docker-compose -f "$COMPOSE_FILE" up -d
    echo "✅ Containers started!"
    echo ""
    echo "MySQL is available at: 127.0.0.1:3306"
    echo "phpMyAdmin is available at: http://localhost:8080"
}

# Stop containers
docker-down() {
    echo "🛑 Stopping Docker containers..."
    docker-compose -f "$COMPOSE_FILE" down
    echo "✅ Containers stopped!"
}

# Stop and remove volumes
docker-reset() {
    echo "⚠️  Removing containers and data..."
    docker-compose -f "$COMPOSE_FILE" down -v
    echo "✅ Reset complete!"
}

# View logs
docker-logs() {
    docker-compose -f "$COMPOSE_FILE" logs -f "$@"
}

# Check status
docker-status() {
    docker-compose -f "$COMPOSE_FILE" ps
}

# Execute MySQL command
docker-mysql() {
    docker exec -it penurwill-mysql mysql -u mysql -ppassword penurwill "$@"
}

# Execute MySQL as root
docker-mysql-root() {
    docker exec -it penurwill-mysql mysql -u root -proot "$@"
}

echo "✅ Docker helpers loaded!"
echo ""
echo "Available commands:"
echo "  docker-up       - Start containers"
echo "  docker-down     - Stop containers"
echo "  docker-reset    - Stop containers and remove data"
echo "  docker-logs     - View container logs"
echo "  docker-status   - Check container status"
echo "  docker-mysql    - Run MySQL commands"
echo "  docker-mysql-root - Run MySQL commands as root"
