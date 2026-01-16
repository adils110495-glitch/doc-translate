<?php

/**
 * Document Translator
 * Translates DOCX documents using DeepL API (via official PHP SDK) while preserving layout and formatting
 * Uses paragraph-level translation for better context
 */
class DocumentTranslator {

    private $deeplApiKey;
    private $deeplApiUrl;
    private $translator;

    public function __construct() {
        // Load Composer autoloader if available
        $this->loadComposerAutoloader();

        // Load environment variables from .env file
        $this->loadEnv();

        // Get DeepL API credentials
        $this->deeplApiKey = getenv('DEEPL_API_KEY');
        $this->deeplApiUrl = getenv('DEEPL_API_URL') ?: 'https://api-free.deepl.com';

        // Initialize DeepL Translator if SDK is available and key is set
        if ($this->deeplApiKey && class_exists('DeepL\Translator')) {
            try {
                $options = [];
                
                // Set server URL based on API type
                if (strpos($this->deeplApiUrl, 'api-free.deepl.com') !== false) {
                    $options['server_url'] = 'https://api-free.deepl.com';
                }
                
                $this->translator = new \DeepL\Translator($this->deeplApiKey, $options);
            } catch (Exception $e) {
                error_log('DeepL Translator initialization failed: ' . $e->getMessage());
                $this->translator = null;
            }
        }
    }

    /**
     * Load Composer autoloader
     */
    private function loadComposerAutoloader() {
        $autoloadPaths = [
            dirname(__DIR__) . '/vendor/autoload.php',
            dirname(dirname(__DIR__)) . '/vendor/autoload.php',
        ];

        foreach ($autoloadPaths as $path) {
            if (file_exists($path)) {
                require_once $path;
                return;
            }
        }
    }

    /**
     * Load environment variables from .env file
     */
    private function loadEnv() {
        $envFile = dirname(__DIR__) . '/.env';

        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                // Skip comments
                if (strpos(trim($line), '#') === 0) {
                    continue;
                }

                // Parse KEY=VALUE
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value);

                    if (!empty($key) && !empty($value)) {
                        putenv("$key=$value");
                    }
                }
            }
        }
    }

    /**
     * Translate a DOCX document
     *
     * @param string $sourcePath Path to source DOCX file
     * @param string $targetPath Path to save translated DOCX file
     * @param string $targetLanguage Target language code
     * @return bool Success status
     */
    public function translate($sourcePath, $targetPath, $targetLanguage) {
        try {
            // Copy the original file to preserve structure
            if (!copy($sourcePath, $targetPath)) {
                return false;
            }

            // DOCX files are ZIP archives - extract and process
            $zip = new ZipArchive();

            if ($zip->open($targetPath) !== true) {
                @unlink($targetPath);
                return false;
            }

            // Extract document.xml (main content)
            $documentXml = $zip->getFromName('word/document.xml');

            if ($documentXml === false) {
                $zip->close();
                @unlink($targetPath);
                return false;
            }

            // Parse XML
            $dom = new DOMDocument();
            $dom->loadXML($documentXml);

            // Find all paragraphs
            $xpath = new DOMXPath($dom);
            $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

            // Get all paragraph elements (w:p)
            $paragraphs = $xpath->query('//w:p');

            if ($paragraphs->length === 0) {
                $zip->close();
                return true; // Empty document is technically successful
            }

            // Extract paragraphs for translation
            $paragraphsToTranslate = [];
            $paragraphMapping = []; // Maps paragraph index to its text nodes

            foreach ($paragraphs as $paragraphIndex => $paragraph) {
                // Get all text nodes within this paragraph
                $textNodes = $xpath->query('.//w:t', $paragraph);
                
                if ($textNodes->length === 0) {
                    continue;
                }

                // Combine all text in the paragraph
                $paragraphText = '';
                $nodeList = [];
                
                foreach ($textNodes as $node) {
                    $text = $node->nodeValue;
                    $paragraphText .= $text;
                    $nodeList[] = $node;
                }

                // Only translate non-empty paragraphs
                if (!empty(trim($paragraphText))) {
                    $paragraphsToTranslate[] = $paragraphText;
                    $paragraphMapping[] = [
                        'text' => $paragraphText,
                        'nodes' => $nodeList
                    ];
                }
            }

            if (empty($paragraphsToTranslate)) {
                $zip->close();
                return true; // No content to translate
            }

            // Translate all paragraphs using DeepL
            $translatedParagraphs = $this->translateTexts($paragraphsToTranslate, $targetLanguage);

            if ($translatedParagraphs === false) {
                $zip->close();
                @unlink($targetPath);
                return false;
            }

            // Replace text in paragraphs
            foreach ($paragraphMapping as $index => $mapping) {
                if (!isset($translatedParagraphs[$index])) {
                    continue;
                }

                $originalText = $mapping['text'];
                $translatedText = $translatedParagraphs[$index];
                $nodes = $mapping['nodes'];

                // Replace text: put all translated text in first node, clear others
                if (count($nodes) > 0) {
                    $nodes[0]->nodeValue = $translatedText;
                    
                    // Clear other nodes in the same paragraph
                    for ($i = 1; $i < count($nodes); $i++) {
                        $nodes[$i]->nodeValue = '';
                    }
                }
            }

            // Save modified XML back to ZIP
            $modifiedXml = $dom->saveXML();

            if (!$zip->deleteName('word/document.xml')) {
                $zip->close();
                @unlink($targetPath);
                return false;
            }

            if (!$zip->addFromString('word/document.xml', $modifiedXml)) {
                $zip->close();
                @unlink($targetPath);
                return false;
            }

            $zip->close();

            return true;

        } catch (Exception $e) {
            error_log('Translation error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Translate an array of texts using DeepL API
     *
     * @param array $texts Array of texts to translate
     * @param string $targetLanguage Target language code
     * @return array|false Translated texts or false on error
     */
    private function translateTexts($texts, $targetLanguage) {
        if (empty($this->deeplApiKey)) {
            error_log('DeepL API key not configured');
            return false;
        }

        if (empty($texts)) {
            return [];
        }

        // Check if DeepL SDK is available
        if ($this->translator && $this->translator instanceof \DeepL\Translator) {
            return $this->translateWithSDK($texts, $targetLanguage);
        } else {
            // Fallback to cURL implementation
            return $this->translateWithCurl($texts, $targetLanguage);
        }
    }

    /**
     * Translate texts using DeepL PHP SDK
     *
     * @param array $texts Array of texts to translate
     * @param string $targetLanguage Target language code
     * @return array|false Translated texts or false on error
     */
    private function translateWithSDK($texts, $targetLanguage) {
        try {
            // Convert language code format if needed
            $targetLang = $this->convertLanguageCode($targetLanguage);

            // Translate all texts in batches
            // DeepL SDK handles batching automatically
            $options = [
                'preserve_formatting' => true,
                'split_sentences' => 'off',
                'tag_handling' => 'xml'
            ];

            $results = $this->translator->translateText($texts, null, $targetLang, $options);

            // Extract translated texts
            $translations = [];
            if (is_array($results)) {
                foreach ($results as $result) {
                    $translations[] = $result->text;
                }
            } else {
                // Single result
                $translations[] = $results->text;
            }

            return $translations;

        } catch (\DeepL\DeepLException $e) {
            error_log('DeepL SDK error: ' . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log('Translation error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Translate texts using cURL (fallback when SDK is not available)
     *
     * @param array $texts Array of texts to translate
     * @param string $targetLanguage Target language code
     * @return array|false Translated texts or false on error
     */
    private function translateWithCurl($texts, $targetLanguage) {
        // Convert language code format if needed
        $targetLang = $this->convertLanguageCode($targetLanguage);

        // DeepL API supports up to 50 texts per request
        $batchSize = 50;
        $batches = array_chunk($texts, $batchSize);
        $allTranslations = [];

        foreach ($batches as $batch) {
            $translations = $this->translateBatchCurl($batch, $targetLang);

            if ($translations === false) {
                return false;
            }

            $allTranslations = array_merge($allTranslations, $translations);
        }

        return $allTranslations;
    }

    /**
     * Translate a batch of texts using cURL
     *
     * @param array $texts Batch of texts to translate
     * @param string $targetLang Target language code
     * @return array|false Translated texts or false on error
     */
    private function translateBatchCurl($texts, $targetLang) {
        $url = $this->deeplApiUrl . '/v2/translate';

        // Prepare POST data
        $postData = [
            'auth_key' => $this->deeplApiKey,
            'target_lang' => $targetLang,
            'preserve_formatting' => '1',
            'split_sentences' => '0',
            'tag_handling' => 'xml'
        ];

        // Add all texts
        foreach ($texts as $text) {
            $postData['text'][] = $text;
        }

        // Initialize cURL
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($postData),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded'
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);

        curl_close($ch);

        // Check for cURL errors
        if ($response === false) {
            error_log('DeepL API cURL error: ' . $curlError);
            return false;
        }

        // Parse response
        $result = json_decode($response, true);

        // Check for API errors
        if ($httpCode !== 200) {
            $errorMessage = isset($result['message']) ? $result['message'] : 'Unknown error';
            error_log('DeepL API error (HTTP ' . $httpCode . '): ' . $errorMessage);
            return false;
        }

        // Extract translated texts
        $translations = [];
        if (isset($result['translations']) && is_array($result['translations'])) {
            foreach ($result['translations'] as $translation) {
                $translations[] = $translation['text'];
            }
        } else {
            error_log('DeepL API unexpected response format');
            return false;
        }

        return $translations;
    }

    /**
     * Convert language code to DeepL format
     *
     * @param string $languageCode Input language code
     * @return string DeepL language code
     */
    private function convertLanguageCode($languageCode) {
        // Map common language codes to DeepL format
        $languageMap = [
            'EN-US' => 'EN-US',
            'EN-GB' => 'EN-GB',
            'EN' => 'EN-US',
            'ES' => 'ES',
            'FR' => 'FR',
            'DE' => 'DE',
            'IT' => 'IT',
            'PT' => 'PT-PT',
            'PT-BR' => 'PT-BR',
            'RU' => 'RU',
            'ZH' => 'ZH',
            'JA' => 'JA',
            'KO' => 'KO',
            'AR' => 'AR'
        ];

        $upperCode = strtoupper($languageCode);

        return isset($languageMap[$upperCode]) ? $languageMap[$upperCode] : $upperCode;
    }

    /**
     * Check if DeepL API is configured
     *
     * @return bool
     */
    public function isConfigured() {
        return !empty($this->deeplApiKey);
    }

    /**
     * Get API usage statistics
     *
     * @return array|false Usage data or false on error
     */
    public function getUsage() {
        if (empty($this->deeplApiKey)) {
            return false;
        }

        // Use SDK if available
        if ($this->translator && $this->translator instanceof \DeepL\Translator) {
            try {
                $usage = $this->translator->getUsage();
                return [
                    'character_count' => $usage->character->count,
                    'character_limit' => $usage->character->limit
                ];
            } catch (Exception $e) {
                error_log('DeepL usage check failed: ' . $e->getMessage());
                return false;
            }
        }

        // Fallback to cURL
        $url = $this->deeplApiUrl . '/v2/usage';

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query(['auth_key' => $this->deeplApiKey]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 10
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($httpCode === 200) {
            return json_decode($response, true);
        }

        return false;
    }
}
