# Troubleshooting Guide

Common issues and solutions for the Document Translation application.

## Docker Issues

### Internal Server Error (500) on Docker

**Symptom**: When accessing http://localhost:8087, you see "Internal Server Error"

**Cause**: The `.htaccess` file contained `<Directory>` directives which are not allowed in `.htaccess` files.

**Solution**: This has been fixed. If you still encounter this issue:

1. Check Docker logs:
```bash
docker logs doc-translate-app --tail=50
```

2. If you see `<Directory not allowed here`, restart the container:
```bash
docker restart doc-translate-app
```

3. If the issue persists, rebuild:
```bash
docker compose down
docker compose build --no-cache
docker compose up -d
```

### Port Already in Use

**Symptom**: Error starting container: "port is already allocated"

**Solution**:

Option 1 - Change the port in [docker-compose.yml](docker-compose.yml):
```yaml
ports:
  - "8088:80"  # Change 8087 to 8088 or any available port
```

Option 2 - Stop the service using port 8087:
```bash
# Find what's using the port
sudo lsof -i :8087  # Linux/macOS
netstat -ano | findstr :8087  # Windows

# Then stop that service or kill the process
```

### Container Won't Start

**Symptom**: Container exits immediately after starting

**Solution**:

1. Check logs for errors:
```bash
docker logs doc-translate-app
```

2. Verify Docker is running:
```bash
docker ps
```

3. Check system resources:
```bash
docker stats
```

4. Rebuild from scratch:
```bash
docker compose down -v
docker compose build --no-cache
docker compose up -d
```

### Permission Denied Errors

**Symptom**: Can't write files, upload fails

**Solution**:

Fix permissions inside container:
```bash
docker exec -it doc-translate-app chown -R www-data:www-data /var/www/html/translated /var/www/html/logs
docker exec -it doc-translate-app chmod -R 775 /var/www/html/translated /var/www/html/logs
```

### Files Not Persisting

**Symptom**: Translated files disappear after container restart

**Solution**:

Check volume mounts in [docker-compose.yml](docker-compose.yml):
```yaml
volumes:
  - ./translated:/var/www/html/translated
  - ./logs:/var/www/html/logs
```

Verify directories exist:
```bash
ls -la translated/ logs/
```

## PHP/Manual Setup Issues

### Upload Fails

**Symptom**: File upload returns error or times out

**Solutions**:

1. Check PHP settings:
```bash
php -i | grep -E "upload_max_filesize|post_max_size|max_execution_time"
```

2. Increase limits in `php.ini`:
```ini
upload_max_filesize = 50M
post_max_size = 50M
max_execution_time = 300
```

3. Check directory permissions:
```bash
chmod 755 translated/ logs/
chown www-data:www-data translated/ logs/
```

### Missing PHP Extensions

**Symptom**: Errors about missing zip, dom, or XML functions

**Solutions**:

Ubuntu/Debian:
```bash
sudo apt-get update
sudo apt-get install php-zip php-xml php-dom
sudo systemctl restart apache2
```

CentOS/RHEL:
```bash
sudo yum install php-zip php-xml
sudo systemctl restart httpd
```

macOS (Homebrew):
```bash
brew install php
```

Verify installation:
```bash
php -m | grep -E "zip|dom|xml"
```

### Translation Fails

**Symptom**: Upload succeeds but translation fails

**Solutions**:

1. Check logs:
```bash
tail -f logs/translation.log
```

2. Verify file is valid DOCX:
```bash
file uploaded_file.docx
# Should show: Microsoft OOXML
```

3. Check file permissions:
```bash
ls -la translated/
```

4. Test with a simple DOCX file first

## Application Issues

### Files List Not Loading

**Symptom**: Left panel shows "Loading files..." forever

**Solutions**:

1. Check browser console (F12) for JavaScript errors

2. Verify API endpoint:
```bash
# Docker
docker exec -it doc-translate-app curl http://localhost/api/get_files.php

# Manual
curl http://localhost:8000/api/get_files.php
```

3. Check file permissions on `translated/` directory

### Toast Notifications Not Showing

**Symptom**: No feedback after upload or actions

**Solutions**:

1. Check browser console for JavaScript errors

2. Verify CDN access (jQuery and Toastr):
```javascript
// In browser console:
typeof jQuery  // Should be "function"
typeof toastr  // Should be "object"
```

3. Check internet connection (CDNs may be blocked)

4. Consider hosting jQuery and Toastr locally if CDN is blocked

### Download Fails

**Symptom**: Click download but nothing happens

**Solutions**:

1. Check browser console for errors

2. Verify file exists:
```bash
ls -la translated/ProjectName/TopicName/
```

3. Check file permissions:
```bash
chmod 644 translated/ProjectName/TopicName/*.docx
```

4. Try direct URL in browser:
```
http://localhost:8087/api/download.php?file=ProjectName/TopicName/filename.docx
```

### Bulk Download Creates Empty ZIP

**Symptom**: ZIP downloads but contains no files

**Solutions**:

1. Check selected files exist:
```bash
ls -la translated/ProjectName/TopicName/
```

2. Verify ZipArchive extension:
```bash
php -m | grep zip
```

3. Check PHP error logs:
```bash
# Docker
docker exec -it doc-translate-app tail -f /var/log/apache2/error.log

# Manual
tail -f /var/log/apache2/error.log
```

## Network Issues

### Can't Access Application

**Symptom**: Browser can't connect to localhost:8087

**Solutions**:

1. Verify container is running:
```bash
docker ps | grep doc-translate
```

2. Check port mapping:
```bash
docker port doc-translate-app
# Should show: 80/tcp -> 0.0.0.0:8087
```

3. Test from command line:
```bash
curl http://localhost:8087
```

4. Check firewall:
```bash
# Linux
sudo ufw status
sudo ufw allow 8087

# Windows
# Check Windows Firewall settings
```

5. Try 127.0.0.1 instead of localhost:
```
http://127.0.0.1:8087
```

### CORS Errors

**Symptom**: Browser console shows CORS policy errors

**Solution**:

This shouldn't happen with same-origin requests. If you see CORS errors:

1. Ensure you're accessing via the same protocol and port
2. Don't use file:// protocol (use http://)
3. Check browser security settings

## Database/Storage Issues

### Logs Directory Not Writable

**Symptom**: Errors about unable to write log file

**Solutions**:

```bash
# Docker
docker exec -it doc-translate-app chmod 775 /var/www/html/logs
docker exec -it doc-translate-app chown www-data:www-data /var/www/html/logs

# Manual
chmod 775 logs/
chown www-data:www-data logs/
```

### Translated Directory Not Writable

**Symptom**: Upload fails with "Failed to save uploaded file"

**Solutions**:

```bash
# Docker
docker exec -it doc-translate-app chmod 775 /var/www/html/translated
docker exec -it doc-translate-app chown -R www-data:www-data /var/www/html/translated

# Manual
chmod 775 translated/
chown -R www-data:www-data translated/
```

## Performance Issues

### Slow Translation

**Symptom**: Translations take a very long time

**Solutions**:

1. Check file size (large files take longer)
2. Monitor system resources:
```bash
docker stats doc-translate-app
```

3. Increase PHP memory limit in [Dockerfile](Dockerfile):
```dockerfile
RUN echo "memory_limit = 512M" >> /usr/local/etc/php/conf.d/uploads.ini
```

4. Check if using real translation API (may have rate limits)

### High Memory Usage

**Symptom**: Container or PHP uses excessive memory

**Solutions**:

1. Limit container memory in [docker-compose.yml](docker-compose.yml):
```yaml
deploy:
  resources:
    limits:
      memory: 512M
```

2. Process smaller batches of files

3. Restart container periodically

## Debugging Commands

### Check Container Status
```bash
docker ps -a
docker inspect doc-translate-app
docker logs doc-translate-app --tail=100 -f
```

### Check Inside Container
```bash
docker exec -it doc-translate-app bash
ls -la /var/www/html/
cat /var/www/html/logs/translation.log
tail -f /var/log/apache2/error.log
```

### Check PHP Configuration
```bash
docker exec -it doc-translate-app php -i | grep -E "upload|memory|execution"
docker exec -it doc-translate-app php -m
```

### Check File Permissions
```bash
docker exec -it doc-translate-app ls -la /var/www/html/translated
docker exec -it doc-translate-app ls -la /var/www/html/logs
```

### Test API Endpoints
```bash
# Get files list
curl http://localhost:8087/api/get_files.php

# Test from inside container
docker exec -it doc-translate-app curl http://localhost/api/get_files.php
```

## Getting More Help

If you're still experiencing issues:

1. Check the application logs: `logs/translation.log`
2. Check Docker logs: `docker logs doc-translate-app`
3. Check Apache error logs: `docker exec -it doc-translate-app tail -f /var/log/apache2/error.log`
4. Review [README.md](README.md) for configuration options
5. Check [DOCKER.md](DOCKER.md) for Docker-specific help

## Common Error Messages

| Error Message | Solution |
|--------------|----------|
| `<Directory not allowed here` | Fixed in latest `.htaccess` - restart container |
| `Failed to create directory` | Check permissions on `translated/` |
| `File upload error: 1` | File exceeds upload_max_filesize |
| `File upload error: 2` | File exceeds MAX_FILE_SIZE |
| `Only DOCX files are allowed` | Upload a valid .docx file |
| `Invalid file type` | File is not a valid DOCX document |
| `Translation failed` | Check logs for specific error |
| `No files specified` | Select files before bulk action |
| `Port already allocated` | Change port or stop conflicting service |

---

**Still need help?** Check the logs first, they usually contain the answer!
