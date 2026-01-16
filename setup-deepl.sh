#!/bin/bash

# DeepL API Setup Script
# This script helps you configure DeepL API for the Document Translation app

echo "============================================"
echo "   DeepL API Setup for Doc Translate"
echo "============================================"
echo ""

# Check if .env file exists
if [ -f ".env" ]; then
    echo "✓ .env file already exists"
    read -p "Do you want to reconfigure? (y/n): " reconfigure
    if [ "$reconfigure" != "y" ]; then
        echo "Setup cancelled."
        exit 0
    fi
fi

echo ""
echo "Please enter your DeepL API key:"
echo "(Get it from: https://www.deepl.com/pro-api)"
read -p "API Key: " api_key

if [ -z "$api_key" ]; then
    echo "Error: API key cannot be empty"
    exit 1
fi

echo ""
echo "Select API type:"
echo "1) DeepL API Free (api-free.deepl.com)"
echo "2) DeepL API Pro (api.deepl.com)"
read -p "Choice (1 or 2): " api_choice

if [ "$api_choice" == "1" ]; then
    api_url="https://api-free.deepl.com"
    echo "Selected: DeepL API Free"
elif [ "$api_choice" == "2" ]; then
    api_url="https://api.deepl.com"
    echo "Selected: DeepL API Pro"
else
    echo "Invalid choice. Defaulting to Free API."
    api_url="https://api-free.deepl.com"
fi

# Create .env file
echo ""
echo "Creating .env file..."

cat > .env << EOF
# DeepL API Configuration
# Generated on $(date)

DEEPL_API_KEY=$api_key
DEEPL_API_URL=$api_url
EOF

chmod 600 .env

echo "✓ .env file created successfully"
echo ""

# Test configuration
echo "Testing DeepL API connection..."
test_result=$(php -r "
putenv('DEEPL_API_KEY=$api_key');
putenv('DEEPL_API_URL=$api_url');
require 'api/translator.php';
\$translator = new DocumentTranslator();
if (\$translator->isConfigured()) {
    echo 'SUCCESS';
    \$usage = \$translator->getUsage();
    if (\$usage && isset(\$usage['character_count'])) {
        echo '|' . \$usage['character_count'] . '|' . \$usage['character_limit'];
    }
} else {
    echo 'FAILED';
}
" 2>&1)

if [[ $test_result == SUCCESS* ]]; then
    echo "✓ DeepL API connection successful!"

    if [[ $test_result == *"|"* ]]; then
        IFS='|' read -ra USAGE <<< "$test_result"
        char_count="${USAGE[1]}"
        char_limit="${USAGE[2]}"
        echo ""
        echo "API Usage:"
        echo "  Used: $char_count characters"
        echo "  Limit: $char_limit characters"
        percent=$(echo "scale=2; $char_count * 100 / $char_limit" | bc 2>/dev/null || echo "0")
        echo "  Usage: ${percent}%"
    fi
else
    echo "✗ DeepL API connection failed!"
    echo "  Please check your API key and try again."
    echo "  Error: $test_result"
    exit 1
fi

echo ""
echo "============================================"
echo "   Setup Complete!"
echo "============================================"
echo ""
echo "Next steps:"
echo ""

# Check if Docker is running
if command -v docker &> /dev/null && docker ps &> /dev/null; then
    echo "Docker detected. To apply changes:"
    echo "  docker-compose down"
    echo "  docker-compose up -d"
    echo ""

    read -p "Restart Docker container now? (y/n): " restart_docker
    if [ "$restart_docker" == "y" ]; then
        echo ""
        echo "Restarting Docker container..."
        docker-compose down
        docker-compose up -d
        echo "✓ Container restarted"
        echo ""
        echo "Access your application at: http://localhost:8087"
    fi
else
    echo "Manual setup detected. Start your server:"
    echo "  php -S localhost:8000"
    echo ""
    echo "Access your application at: http://localhost:8000"
fi

echo ""
echo "Documentation:"
echo "  - Setup guide: DEEPL_SETUP.md"
echo "  - Quick start: QUICKSTART.md"
echo ""
echo "Happy translating! 🚀"
