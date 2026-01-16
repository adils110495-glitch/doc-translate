# Docker Setup Guide

Complete guide for running the Document Translation application using Docker.

## Prerequisites

- **Docker**: Version 20.10 or higher
- **Docker Compose**: Version 1.29 or higher

### Install Docker

#### Linux (Ubuntu/Debian)
```bash
sudo apt-get update
sudo apt-get install docker.io docker-compose
sudo systemctl start docker
sudo systemctl enable docker
```

#### macOS
```bash
# Install Docker Desktop from:
# https://www.docker.com/products/docker-desktop
```

#### Windows
```bash
# Install Docker Desktop from:
# https://www.docker.com/products/docker-desktop
```

Verify installation:
```bash
docker --version
docker-compose --version
```

## Quick Start

### 1. Start the Application

```bash
# Build and start in detached mode
docker-compose up -d
```

This will:
- Build the Docker image with PHP 8.1 and Apache
- Install required PHP extensions (zip, dom, libxml)
- Configure PHP settings (50MB upload limit)
- Start the container on port **8087**

### 2. Access the Application

Open your browser and navigate to:
```
http://localhost:8087
```

### 3. Check Container Status

```bash
# View running containers
docker-compose ps

# View logs
docker-compose logs -f

# View specific container logs
docker logs doc-translate-app
```

### 4. Stop the Application

```bash
# Stop and remove containers
docker-compose down

# Stop, remove containers, and delete volumes
docker-compose down -v
```

## Docker Configuration

### Port Configuration

The application runs on port **8087** by default (mapped from container port 80).

To change the port, edit [docker-compose.yml](docker-compose.yml):

```yaml
ports:
  - "8087:80"  # Change 8087 to your desired port
```

Then restart:
```bash
docker-compose down
docker-compose up -d
```

### Volume Mounts

Data is persisted using Docker volumes:

```yaml
volumes:
  - ./translated:/var/www/html/translated  # Translated files
  - ./logs:/var/www/html/logs             # Activity logs
```

**Benefits**:
- Files persist after container restarts
- Direct access to files on host system
- Easy backup and migration

### Development Mode

For live code changes without rebuilding, uncomment these lines in [docker-compose.yml](docker-compose.yml):

```yaml
volumes:
  - ./api:/var/www/html/api
  - ./assets:/var/www/html/assets
  - ./index.php:/var/www/html/index.php
```

Changes to PHP/JS/CSS files will be reflected immediately.

## Docker Commands Reference

### Container Management

```bash
# Start containers
docker-compose up -d

# Stop containers
docker-compose down

# Restart containers
docker-compose restart

# View running containers
docker-compose ps

# View all containers (including stopped)
docker ps -a
```

### Logs and Debugging

```bash
# View all logs
docker-compose logs

# Follow logs in real-time
docker-compose logs -f

# View last 100 lines
docker-compose logs --tail=100

# View logs for specific container
docker logs doc-translate-app
```

### Accessing the Container

```bash
# Execute bash inside container
docker exec -it doc-translate-app bash

# View PHP configuration
docker exec -it doc-translate-app php -i

# Check installed extensions
docker exec -it doc-translate-app php -m

# View Apache error log
docker exec -it doc-translate-app tail -f /var/log/apache2/error.log
```

### Image Management

```bash
# Rebuild image
docker-compose build

# Rebuild without cache
docker-compose build --no-cache

# Pull latest base image
docker-compose pull

# Remove unused images
docker image prune -a
```

### Data Management

```bash
# View volumes
docker volume ls

# Backup translated files
docker cp doc-translate-app:/var/www/html/translated ./backup/

# Restore translated files
docker cp ./backup/translated doc-translate-app:/var/www/html/

# Clean up volumes
docker-compose down -v
```

## File Structure in Container

```
Container: /var/www/html/
├── index.php
├── api/
├── assets/
├── translated/  (mounted volume)
├── logs/        (mounted volume)
└── ...
```

## PHP Configuration

The Docker container is pre-configured with:

```ini
upload_max_filesize = 50M
post_max_size = 50M
max_execution_time = 300
max_input_time = 300
memory_limit = 256M
```

To modify these settings, edit [Dockerfile](Dockerfile):

```dockerfile
RUN echo "upload_max_filesize = 100M" >> /usr/local/etc/php/conf.d/uploads.ini
```

Then rebuild:
```bash
docker-compose down
docker-compose build
docker-compose up -d
```

## Troubleshooting

### Container won't start

```bash
# Check logs
docker-compose logs

# Check port conflicts
sudo lsof -i :8087  # Linux/macOS
netstat -ano | findstr :8087  # Windows
```

### Permission Issues

```bash
# Fix permissions inside container
docker exec -it doc-translate-app chown -R www-data:www-data /var/www/html/translated /var/www/html/logs
docker exec -it doc-translate-app chmod -R 775 /var/www/html/translated /var/www/html/logs
```

### Port Already in Use

Change the port in [docker-compose.yml](docker-compose.yml):
```yaml
ports:
  - "8088:80"  # Use different port
```

### Files Not Persisting

Ensure volumes are mounted correctly:
```bash
docker-compose down
docker-compose up -d
docker exec -it doc-translate-app ls -la /var/www/html/translated
```

### Can't Access Application

1. Check container is running:
   ```bash
   docker-compose ps
   ```

2. Check Apache is running:
   ```bash
   docker exec -it doc-translate-app service apache2 status
   ```

3. Check firewall settings:
   ```bash
   sudo ufw allow 8087  # Linux
   ```

## Production Deployment

### Using Docker Compose

1. **Clone repository on server**:
```bash
git clone <repository-url>
cd doc-translate
```

2. **Start in production mode**:
```bash
docker-compose up -d
```

3. **Set up reverse proxy** (Nginx example):
```nginx
server {
    listen 80;
    server_name yourdomain.com;

    location / {
        proxy_pass http://localhost:8087;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

4. **Set up SSL with Let's Encrypt**:
```bash
sudo certbot --nginx -d yourdomain.com
```

### Environment Variables

Create `.env` file for production:
```env
COMPOSE_PROJECT_NAME=doc-translate
APACHE_PORT=8087
```

Update [docker-compose.yml](docker-compose.yml):
```yaml
ports:
  - "${APACHE_PORT}:80"
```

## Backup and Restore

### Backup

```bash
# Backup translated files
tar -czf backup_translated_$(date +%Y%m%d).tar.gz translated/

# Backup logs
tar -czf backup_logs_$(date +%Y%m%d).tar.gz logs/

# Backup entire application
docker-compose down
tar -czf backup_full_$(date +%Y%m%d).tar.gz .
docker-compose up -d
```

### Restore

```bash
# Stop container
docker-compose down

# Restore files
tar -xzf backup_translated_20260113.tar.gz
tar -xzf backup_logs_20260113.tar.gz

# Start container
docker-compose up -d
```

## Health Checks

Add health check to [docker-compose.yml](docker-compose.yml):

```yaml
services:
  doc-translate:
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost/"]
      interval: 30s
      timeout: 10s
      retries: 3
      start_period: 40s
```

Check health:
```bash
docker-compose ps
docker inspect doc-translate-app | grep -A 10 Health
```

## Monitoring

### View Resource Usage

```bash
# Real-time stats
docker stats doc-translate-app

# Detailed info
docker inspect doc-translate-app
```

### Log Rotation

Configure log rotation in [docker-compose.yml](docker-compose.yml):

```yaml
services:
  doc-translate:
    logging:
      driver: "json-file"
      options:
        max-size: "10m"
        max-file: "3"
```

## Security Best Practices

1. **Run as non-root** (already configured)
2. **Use specific versions** in Dockerfile:
   ```dockerfile
   FROM php:8.1.27-apache
   ```
3. **Scan for vulnerabilities**:
   ```bash
   docker scan doc-translate-app
   ```
4. **Update base image regularly**:
   ```bash
   docker-compose pull
   docker-compose up -d
   ```

## Support

For issues related to:
- **Docker setup**: Check this guide
- **Application features**: See [README.md](README.md)
- **Quick start**: See [QUICKSTART.md](QUICKSTART.md)

---

**Docker setup complete!** 🐳 Access your application at http://localhost:8087
