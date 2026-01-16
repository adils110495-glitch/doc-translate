# Document Translation Application

A PHP-based web application for translating DOCX documents while preserving their original layout, formatting, and structure.

## Features

- **Split-screen interface**: Left side for translated files explorer, right side for upload form
- **Background translation**: Upload and translate documents without blocking the UI
- **Toast notifications**: Real-time feedback for all operations
- **Layout preservation**: Maintains original document formatting, tables, headings, and structure
- **Organized storage**: Files grouped by project and topic
- **Bulk operations**: Download or delete multiple files at once
- **Comprehensive logging**: All translation activities logged with timestamps
- **Security**: Input sanitization, file validation, and directory traversal protection

## Requirements

- PHP 7.4 or higher
- PHP extensions:
  - `zip` - For handling DOCX files
  - `dom` - For XML parsing
  - `libxml` - For XML processing
- Web server (Apache, Nginx, or PHP built-in server)
- Write permissions for `translated/` and `logs/` directories

## Installation

### Option 1: Docker (Recommended)

The easiest way to run the application is using Docker:

1. **Ensure Docker and Docker Compose are installed**:
```bash
docker --version
docker-compose --version
```

2. **Clone or download this repository**:
```bash
git clone <repository-url>
cd doc-translate
```

3. **Build and start the container**:
```bash
docker-compose up -d
```

4. **Access the application**:
```
http://localhost:8087
```

5. **View logs** (optional):
```bash
docker-compose logs -f
```

6. **Stop the container**:
```bash
docker-compose down
```

**Docker Benefits**:
- No need to install PHP or extensions manually
- Pre-configured environment
- Consistent across all platforms (Windows, macOS, Linux)
- Easy to start/stop
- Persistent data via volumes

### Option 2: Manual Installation

1. Clone or download this repository to your web server directory

2. Ensure PHP has the required extensions:
```bash
php -m | grep -E "zip|dom|libxml"
```

3. Set proper permissions:
```bash
chmod 755 translated/ logs/
chmod 644 api/*.php
```

4. Configure your web server to point to the project directory, or use PHP built-in server:
```bash
php -S localhost:8000
```

5. Open your browser and navigate to:
```
http://localhost:8000
```

## Directory Structure

```
doc-translate/
├── index.php              # Main application interface
├── instructions.md        # Original requirements
├── README.md             # This file
├── assets/
│   ├── css/
│   │   └── style.css     # Application styles
│   └── js/
│       └── script.js     # Frontend JavaScript
├── api/
│   ├── config.php        # Configuration settings
│   ├── logger.php        # Logging functions
│   ├── translator.php    # Translation engine
│   ├── translate.php     # Upload & translate endpoint
│   ├── get_files.php     # Get translated files endpoint
│   ├── download.php      # Single file download
│   ├── bulk_download.php # Bulk download (ZIP)
│   └── bulk_delete.php   # Bulk delete endpoint
├── translated/           # Translated files storage
│   └── {project}/
│       └── {topic}/
│           ├── original_*.docx
│           └── translated_*_{lang}_*.docx
└── logs/
    └── translation.log   # Activity logs
```

## Usage

### Upload & Translate Documents

1. Select a DOCX file
2. Choose target language from dropdown
3. Enter project name (e.g., "ProjectA")
4. Enter topic name (e.g., "Legal")
5. Click "Translate Document"

The translation will run in the background, and you'll receive toast notifications for:
- Upload started
- Translation completed/failed

### View Translated Files

The left panel shows all translated files grouped by:
- Project name
- Topic name

Each file displays:
- File name
- Target language
- Date & time

### Download Files

- **Single file**: Click the "Download" button next to any file
- **Multiple files**: Select checkboxes and click "Download Selected" (creates ZIP)

### Delete Files

1. Select files using checkboxes
2. Click "Delete Selected"
3. Confirm deletion in popup
4. Selected files will be deleted and removed from the list

### View Logs

All translation activities are logged in `logs/translation.log`:

```
[2026-01-13 15:40:12] SUCCESS | contract.docx | ProjectA | Legal | EN-GB
[2026-01-13 15:42:05] FAILED | report.docx | ProjectB | Finance | ES | Error: File too large
[2026-01-13 15:45:30] DELETED | contract_EN-GB_1234567890.docx | ProjectA | Legal
```

## Translation API Integration

### ✅ DeepL API (Configured and Ready)

This application is **pre-configured to use DeepL API** for high-quality translations. To get started:

#### Quick Setup

1. **Get your DeepL API key**:
   - Visit [https://www.deepl.com/pro-api](https://www.deepl.com/pro-api)
   - Sign up for **DeepL API Free** (500,000 characters/month free)
   - Get your API key from your account page

2. **Run the setup script**:
   ```bash
   ./setup-deepl.sh
   ```
   The script will guide you through the configuration process.

3. **Or configure manually**:
   ```bash
   cp .env.example .env
   nano .env
   ```
   Add your API key:
   ```env
   DEEPL_API_KEY=your-api-key-here:fx
   DEEPL_API_URL=https://api-free.deepl.com
   ```

4. **Restart the application**:
   ```bash
   # Docker
   docker-compose down && docker-compose up -d

   # Manual
   # Just reload the page
   ```

**That's it!** Your translations will now use DeepL API automatically.

For detailed setup instructions, see [DEEPL_SETUP.md](DEEPL_SETUP.md).

### Alternative Translation Services

If you prefer to use a different translation service, you can modify [api/translator.php](api/translator.php):

#### Option 1: Google Cloud Translation API

```php
// Install: composer require google/cloud-translate

use Google\Cloud\Translate\V2\TranslateClient;

private function translateTexts($texts, $targetLanguage) {
    $translate = new TranslateClient(['key' => 'YOUR_API_KEY']);
    $translated = [];

    foreach ($texts as $text) {
        $result = $translate->translate($text, ['target' => $targetLanguage]);
        $translated[] = $result['text'];
    }

    return $translated;
}
```

#### Option 2: DeepL API

```php
// Install: composer require deeplcom/deepl-php

use DeepL\Translator;

private function translateTexts($texts, $targetLanguage) {
    $translator = new Translator('YOUR_API_KEY');
    $translated = [];

    foreach ($texts as $text) {
        $result = $translator->translateText($text, null, $targetLanguage);
        $translated[] = $result->text;
    }

    return $translated;
}
```

#### Option 3: LibreTranslate (Self-hosted/Free)

```php
private function translateTexts($texts, $targetLanguage) {
    $translated = [];
    $apiUrl = 'https://libretranslate.com/translate'; // Or your self-hosted URL

    foreach ($texts as $text) {
        $response = file_get_contents($apiUrl, false, stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => json_encode([
                    'q' => $text,
                    'source' => 'auto',
                    'target' => strtolower(substr($targetLanguage, 0, 2)),
                    'api_key' => 'YOUR_API_KEY' // Optional for self-hosted
                ])
            ]
        ]));

        $result = json_decode($response, true);
        $translated[] = $result['translatedText'] ?? $text;
    }

    return $translated;
}
```

## Security Features

- File type validation (extension + MIME type)
- File size limit (50MB)
- Input sanitization for all user inputs
- Directory traversal protection
- Alphanumeric validation for project/topic names
- Secure file path handling

## Configuration

Edit [api/config.php](api/config.php) to customize:

```php
// Maximum file size (default: 50MB)
define('MAX_FILE_SIZE', 50 * 1024 * 1024);

// Timezone for logging
date_default_timezone_set('UTC');
```

## Browser Support

- Chrome/Edge (latest)
- Firefox (latest)
- Safari (latest)
- Opera (latest)

## Troubleshooting

### Files not uploading

- Check PHP `upload_max_filesize` and `post_max_size` in php.ini
- Ensure `translated/` directory has write permissions
- Check browser console for JavaScript errors

### Translations failing

- Verify ZipArchive extension is installed: `php -m | grep zip`
- Check file permissions on uploaded files
- Review `logs/translation.log` for error details

### Toast notifications not showing

- Ensure jQuery and Toastr CDN links are accessible
- Check browser console for JavaScript errors
- Verify network connectivity

## License

This project is provided as-is for educational and commercial use.

## Contributing

Feel free to submit issues, fork the repository, and create pull requests for any improvements.

## Support

For issues or questions, please check the logs first, then review the troubleshooting section above.
