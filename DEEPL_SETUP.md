# DeepL API Setup Guide

Complete guide for integrating DeepL API with the Document Translation application.

## What is DeepL?

DeepL is a neural machine translation service that provides high-quality translations. It supports 31+ languages and is known for producing more natural-sounding translations compared to other services.

## Getting Your DeepL API Key

### Option 1: DeepL API Free (Recommended for Testing)

1. Visit [https://www.deepl.com/pro-api](https://www.deepl.com/pro-api)
2. Click on "Sign up for free"
3. Create an account
4. Choose the **DeepL API Free** plan
   - 500,000 characters/month free
   - Perfect for testing and small projects
5. After signup, go to your [Account page](https://www.deepl.com/account/summary)
6. Find your **Authentication Key** in the API section
   - Free API keys end with `:fx`

### Option 2: DeepL API Pro (For Production)

1. Visit [https://www.deepl.com/pro-api](https://www.deepl.com/pro-api)
2. Choose **DeepL API Pro** plan
3. Pricing: Pay-as-you-go starting at $5.49 per million characters
4. Get your Authentication Key from account page

## Configuration

### Step 1: Create .env File

1. Copy the example environment file:
```bash
cp .env.example .env
```

2. Edit the `.env` file:
```bash
nano .env
# or use any text editor
```

3. Add your DeepL API key:

**For DeepL Free API:**
```env
DEEPL_API_KEY=your-api-key-here:fx
DEEPL_API_URL=https://api-free.deepl.com
```

**For DeepL Pro API:**
```env
DEEPL_API_KEY=your-api-key-here
DEEPL_API_URL=https://api.deepl.com
```

### Step 2: Verify Configuration

#### Manual Setup

Test your API key:
```bash
php -r "
putenv('DEEPL_API_KEY=your-api-key-here:fx');
putenv('DEEPL_API_URL=https://api-free.deepl.com');
require 'api/translator.php';
\$translator = new DocumentTranslator();
if (\$translator->isConfigured()) {
    echo 'DeepL API is configured correctly!\n';
    \$usage = \$translator->getUsage();
    if (\$usage) {
        echo 'Character count: ' . \$usage['character_count'] . '/' . \$usage['character_limit'] . '\n';
    }
} else {
    echo 'DeepL API key not found or invalid\n';
}
"
```

#### Docker Setup

1. Create the `.env` file in your project root (same location as `docker-compose.yml`)

2. Rebuild and restart the container:
```bash
docker-compose down
docker-compose build
docker-compose up -d
```

3. Copy `.env` file into container (if not using volume mount):
```bash
docker cp .env doc-translate-app:/var/www/html/.env
docker restart doc-translate-app
```

4. Test inside container:
```bash
docker exec -it doc-translate-app php -r "
require '/var/www/html/api/translator.php';
\$translator = new DocumentTranslator();
echo \$translator->isConfigured() ? 'Configured' : 'Not configured';
"
```

## Supported Languages

DeepL supports the following languages:

| Code | Language |
|------|----------|
| EN-US | English (American) |
| EN-GB | English (British) |
| DE | German |
| FR | French |
| ES | Spanish |
| IT | Italian |
| PT-PT | Portuguese (European) |
| PT-BR | Portuguese (Brazilian) |
| RU | Russian |
| ZH | Chinese (Simplified) |
| JA | Japanese |
| KO | Korean |
| AR | Arabic |
| BG | Bulgarian |
| CS | Czech |
| DA | Danish |
| EL | Greek |
| ET | Estonian |
| FI | Finnish |
| HU | Hungarian |
| ID | Indonesian |
| LV | Latvian |
| LT | Lithuanian |
| NL | Dutch |
| PL | Polish |
| RO | Romanian |
| SK | Slovak |
| SL | Slovenian |
| SV | Swedish |
| TR | Turkish |
| UK | Ukrainian |

Note: Some languages may only be available with Pro API. Check [DeepL documentation](https://developers.deepl.com/docs) for the latest list.

## Usage Limits

### DeepL API Free
- **500,000 characters per month** (free)
- Suitable for:
  - Testing and development
  - Small personal projects
  - Up to ~100 pages of documents per month

### DeepL API Pro
- **Pay-as-you-go pricing**
- $5.49 per million characters
- No monthly limit
- Suitable for:
  - Production environments
  - High-volume translation
  - Business use

### Checking Your Usage

The application automatically tracks API usage. View your usage:

```bash
# Via command line
php -r "
require 'api/translator.php';
\$translator = new DocumentTranslator();
\$usage = \$translator->getUsage();
if (\$usage) {
    echo 'Used: ' . \$usage['character_count'] . ' / ' . \$usage['character_limit'] . ' characters\n';
    \$percent = (\$usage['character_count'] / \$usage['character_limit']) * 100;
    echo 'Usage: ' . number_format(\$percent, 2) . '%\n';
}
"
```

You can also check usage in your [DeepL Account](https://www.deepl.com/account/usage).

## Translation Features

The implementation includes:

### 1. Batch Processing
- Translates multiple text segments in one API call
- Reduces API calls and improves speed
- Handles up to 50 texts per request

### 2. Format Preservation
- `preserve_formatting=1`: Maintains formatting tags
- `split_sentences=0`: Doesn't split sentences, preserving structure
- XML nodes remain intact

### 3. Error Handling
- Automatic retry on network errors
- Detailed error logging
- Graceful fallback if API is unavailable

### 4. Language Mapping
- Automatically converts language codes
- Supports both standard and DeepL-specific codes
- Example: `EN` → `EN-US`

## Troubleshooting

### API Key Not Working

**Symptom**: Translation fails with "DeepL API key not configured"

**Solutions**:

1. Verify `.env` file exists:
```bash
ls -la .env
```

2. Check API key format:
   - Free API keys must end with `:fx`
   - Pro API keys don't have the `:fx` suffix

3. Verify API URL:
   - Free: `https://api-free.deepl.com`
   - Pro: `https://api.deepl.com`

4. Test API key directly:
```bash
curl -X POST 'https://api-free.deepl.com/v2/translate' \
  -d 'auth_key=YOUR_API_KEY:fx' \
  -d 'text=Hello' \
  -d 'target_lang=ES'
```

### Translation Fails

**Symptom**: Upload succeeds but translation returns error

**Solutions**:

1. Check logs:
```bash
tail -f logs/translation.log
```

2. Check DeepL API status:
   - Visit [https://status.deepl.com/](https://status.deepl.com/)

3. Verify language is supported:
   - Check supported languages list above

4. Check character limit:
   - Free API: 500,000 characters/month
   - View usage in DeepL account

### Character Limit Exceeded

**Symptom**: Error "Quota exceeded"

**Solutions**:

1. Check your usage:
```bash
# See usage checking command above
```

2. Wait until next month (Free API resets monthly)

3. Upgrade to DeepL Pro

4. Use smaller documents

### Authentication Error

**Symptom**: HTTP 403 or "Invalid authentication key"

**Solutions**:

1. Verify API key is correct:
   - Check for extra spaces
   - Ensure `:fx` suffix for Free API

2. Regenerate API key:
   - Go to [DeepL Account](https://www.deepl.com/account)
   - Generate new key
   - Update `.env` file

3. Check API URL matches your plan:
   - Free must use `api-free.deepl.com`
   - Pro must use `api.deepl.com`

### Timeout Errors

**Symptom**: Translation times out or fails on large documents

**Solutions**:

1. Increase PHP timeout (already set to 300s)

2. Split large documents into smaller parts

3. Check internet connection

4. Verify DeepL API is accessible:
```bash
ping api-free.deepl.com
```

## Docker Configuration

### Method 1: Environment Variables in docker-compose.yml

Add to [docker-compose.yml](docker-compose.yml):

```yaml
services:
  doc-translate:
    environment:
      - DEEPL_API_KEY=your-api-key-here:fx
      - DEEPL_API_URL=https://api-free.deepl.com
```

Then restart:
```bash
docker-compose down
docker-compose up -d
```

### Method 2: Using .env File (Recommended)

1. Create `.env` file in project root

2. Mount it as volume in [docker-compose.yml](docker-compose.yml):
```yaml
services:
  doc-translate:
    volumes:
      - ./.env:/var/www/html/.env:ro
```

3. Restart container:
```bash
docker-compose down
docker-compose up -d
```

### Method 3: Copy .env into Container

```bash
docker cp .env doc-translate-app:/var/www/html/.env
docker restart doc-translate-app
```

## Security Best Practices

### 1. Protect Your API Key

```bash
# Ensure .env is not tracked by git
echo ".env" >> .gitignore

# Set proper permissions
chmod 600 .env
```

### 2. Use Environment Variables in Production

Never hardcode API keys in code. Always use environment variables.

### 3. Rotate Keys Regularly

Generate new API keys periodically for security.

### 4. Monitor Usage

Set up alerts in your DeepL account for quota limits.

### 5. Use Different Keys for Different Environments

- Development: Free API key
- Staging: Separate Pro API key
- Production: Separate Pro API key

## Cost Estimation

### DeepL API Pro Pricing

- $5.49 per 1,000,000 characters
- Average document: ~5,000 characters
- Cost per document: ~$0.027 (less than 3 cents)

### Example Calculations

| Documents/Month | Characters | Cost/Month |
|----------------|-----------|-----------|
| 100 | 500,000 | $2.75 |
| 500 | 2,500,000 | $13.73 |
| 1,000 | 5,000,000 | $27.45 |
| 10,000 | 50,000,000 | $274.50 |

## Advanced Configuration

### Custom Source Language Detection

DeepL auto-detects source language by default. To specify source language:

Modify [api/translator.php](api/translator.php) line ~220:
```php
$postData = [
    'auth_key' => $this->deeplApiKey,
    'source_lang' => 'EN',  // Add this line
    'target_lang' => $targetLang,
    'preserve_formatting' => '1',
    'split_sentences' => '0'
];
```

### Formality Setting

For languages that support formality (German, French, Italian, Spanish, etc.):

```php
$postData['formality'] = 'more';  // or 'less' or 'default'
```

### Glossary Support

DeepL supports custom glossaries for consistent terminology. See [DeepL Glossary Documentation](https://developers.deepl.com/docs/resources/glossaries).

## Testing

### Test Translation Manually

```bash
php -r "
require 'api/translator.php';
\$translator = new DocumentTranslator();

// Create test DOCX (you need a real DOCX file)
\$source = 'test.docx';
\$target = 'test_translated.docx';

if (\$translator->translate(\$source, \$target, 'DE')) {
    echo 'Translation successful!\n';
} else {
    echo 'Translation failed\n';
}
"
```

### Test API Connection

```bash
curl -X POST 'https://api-free.deepl.com/v2/translate' \
  -d 'auth_key=YOUR_API_KEY:fx' \
  -d 'text=Hello, world!' \
  -d 'target_lang=DE'
```

Expected response:
```json
{
  "translations": [
    {
      "detected_source_language": "EN",
      "text": "Hallo, Welt!"
    }
  ]
}
```

## Support and Resources

- **DeepL Documentation**: [https://developers.deepl.com/docs](https://developers.deepl.com/docs)
- **DeepL Support**: [https://support.deepl.com/](https://support.deepl.com/)
- **API Status**: [https://status.deepl.com/](https://status.deepl.com/)
- **Pricing**: [https://www.deepl.com/pro-api](https://www.deepl.com/pro-api)

## Migration from Mock Translation

If you're upgrading from the mock translation:

1. The application will automatically use DeepL once `.env` is configured
2. No code changes needed in other files
3. Existing translated files remain unchanged
4. New translations will use DeepL API

## FAQ

**Q: Do I need a credit card for the Free API?**
A: No, DeepL API Free doesn't require a credit card.

**Q: What happens if I exceed the free limit?**
A: API calls will fail with quota exceeded error. You'll need to upgrade to Pro or wait until next month.

**Q: Can I translate PDF files?**
A: Currently only DOCX files are supported. You can convert PDF to DOCX first.

**Q: How accurate is DeepL?**
A: DeepL is considered one of the most accurate neural translation services, often preferred over Google Translate for European languages.

**Q: Can I use this commercially?**
A: Yes, both Free and Pro APIs can be used commercially. Check [DeepL Terms of Service](https://www.deepl.com/pro-license).

**Q: Is my data sent to DeepL secure?**
A: Yes, DeepL uses HTTPS encryption. Check [DeepL Security](https://www.deepl.com/security) for details.

---

**Ready to translate!** Once you've configured your API key, start translating documents at http://localhost:8087
