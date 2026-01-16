# Quick Start Guide

## Get Started in 2 Steps (Docker - Recommended)

### 1. Start with Docker

```bash
cd /path/to/doc-translate
docker-compose up -d
```

### 2. Open in Browser

Navigate to: `http://localhost:8087`

**That's it!** Docker handles all dependencies automatically.

---

## Alternative: Manual Setup (3 Steps)

### 1. Check Requirements

Ensure PHP 7.4+ is installed with required extensions:

```bash
php --version
php -m | grep -E "zip|dom|libxml"
```

### 2. Start the Server

```bash
cd /path/to/doc-translate
php -S localhost:8000
```

### 3. Open in Browser

Navigate to: `http://localhost:8000`

## First Translation

1. **Upload a DOCX file** (right side of the screen)
2. **Select target language** from the dropdown
3. **Enter project name** (e.g., "MyProject")
4. **Enter topic name** (e.g., "Documents")
5. **Click "Translate Document"**

Watch for toast notifications showing the progress!

## View Results

- Left side shows all translated files
- Files are grouped by project and topic
- Click "Download" to get individual files
- Select multiple files and click "Download Selected" for bulk ZIP download

## Important Notes

### ⚠️ Configure DeepL API (Required for Real Translation)

The application is **ready to use DeepL API** but requires your API key:

1. **Get free API key**: Visit [https://www.deepl.com/pro-api](https://www.deepl.com/pro-api)
   - 500,000 characters/month free
   - No credit card required

2. **Quick setup**:
   ```bash
   ./setup-deepl.sh
   ```
   OR manually:
   ```bash
   cp .env.example .env
   nano .env
   # Add your DEEPL_API_KEY
   ```

3. **Restart** (Docker only):
   ```bash
   docker-compose down && docker-compose up -d
   ```

**Without API key**: Translations will fail. See [DEEPL_SETUP.md](DEEPL_SETUP.md) for detailed instructions.

### File Limits

- Maximum file size: **50MB**
- Only **.docx** files accepted
- To change limits, edit `MAX_FILE_SIZE` in [api/config.php](api/config.php)

### Logs

All activity is logged in `logs/translation.log`:

```bash
tail -f logs/translation.log
```

## Docker Commands (Quick Reference)

```bash
# Start application
docker-compose up -d

# Stop application
docker-compose down

# View logs
docker-compose logs -f

# Restart application
docker-compose restart

# Access container shell
docker exec -it doc-translate-app bash

# Check status
docker-compose ps
```

For detailed Docker documentation, see [DOCKER.md](DOCKER.md)

## Troubleshooting

### Docker Issues

```bash
# Port already in use - change port in docker-compose.yml
# Then restart:
docker-compose down
docker-compose up -d

# Permission issues
docker exec -it doc-translate-app chown -R www-data:www-data /var/www/html/translated /var/www/html/logs
```

### Manual Setup Issues

#### Upload Fails

```bash
# Check permissions
chmod 755 translated/ logs/

# Verify PHP settings
php -i | grep -E "upload_max_filesize|post_max_size"
```

#### Zip Extension Missing

```bash
# Ubuntu/Debian
sudo apt-get install php-zip

# CentOS/RHEL
sudo yum install php-zip

# macOS (Homebrew)
brew install php
```

## Next Steps

- Review [README.md](README.md) for detailed documentation
- Integrate real translation API for production use
- Customize styling in [assets/css/style.css](assets/css/style.css)
- Configure web server (Apache/Nginx) for production deployment

## Project Structure

```
doc-translate/
├── index.php          → Main interface
├── api/              → Backend endpoints
├── assets/           → CSS & JavaScript
├── translated/       → Stored files (auto-created)
└── logs/            → Activity logs (auto-created)
```

## Need Help?

- Check [README.md](README.md) for full documentation
- Review `logs/translation.log` for errors
- Verify PHP extensions are installed
- Ensure proper file permissions

---

**Ready to translate!** 🚀
