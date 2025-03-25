<?php
namespace JiFramework\Core\Language;

use JiFramework\Config\Config;
use Exception;

class LanguageManager
{
    /**
     * @var array Loaded translation strings.
     */
    private $langStrings = [];

    /**
     * @var string Current language code.
     */
    private $currentLang;

    /**
     * @var array Available languages.
     */
    private $availableLanguages = [];

    /**
     * @var bool Multi-language support enabled.
     */
    private $multiLangEnabled;

    /**
     * @var string Multi-language method ('url' or 'cookie').
     */
    private $multiLangMethod;

    /**
     * @var string Default language code.
     */
    private $defaultLanguage;

    /**
     * @var string Language key used in URL or cookie.
     */
    private $multiLangKey;

    /**
     * @var string Path to the language directory.
     */
    private $languageDir;

    /**
     * Constructor automatically initializes the language system.
     *
     * @throws Exception if multi-language support is disabled.
     */
    public function __construct()
    {
        // Initialize configuration properties
        $this->multiLangEnabled = Config::MULTI_LANG;
        $this->multiLangMethod = Config::MULTI_LANG_METHOD;
        $this->defaultLanguage = Config::MULTI_LANG_DEFAULT_LANG;
        $this->multiLangKey = Config::MULTI_LANG_KEY;
        $this->languageDir = Config::MULTI_LANG_DIR;

        if (!$this->multiLangEnabled) {
            throw new Exception('Multi-language support is disabled in Config.php');
        }

        $this->loadAvailableLanguages();
        $this->currentLang = $this->getCurrentLanguage();
        $this->loadLanguageFile($this->currentLang);
    }

     /**
     * Load available languages by scanning the language directory.
     *
     * @return void
     */
    private function loadAvailableLanguages()
    {
        $languages = [];

        // Scan the language directory for JSON files
        foreach (glob($this->languageDir . '*.json') as $file) {
            $langCode = basename($file, '.json'); // Extract language code from filename
            $content = json_decode(file_get_contents($file), true);

            if (isset($content['langCode']) && isset($content['langName'])) {
                $languages[$langCode] = [
                    'langCode' => $content['langCode'],
                    'langName' => $content['langName'],
                    'isRTL' => isset($content['isRTL']) ? (bool)$content['isRTL'] : false,
                ];
            }
        }

        $this->availableLanguages = $languages;
    }

    /**
     * Check if the provided language code is valid and available.
     *
     * @param string $langCode
     * @return bool
     */
    private function isValidLanguageCode($langCode)
    {
        return array_key_exists($langCode, $this->availableLanguages);
    }

    /**
     * Get current language based on the selected method ('url' or 'cookie').
     *
     * @return string
     */
    private function getCurrentLanguage()
    {
        $lang = $this->defaultLanguage;

        if ($this->multiLangMethod === 'url') {
            // Check if the language is set in the URL (e.g., ?lang=en)
            if (isset($_GET[$this->multiLangKey]) && !empty($_GET[$this->multiLangKey])) {
                $langParam = htmlspecialchars($_GET[$this->multiLangKey], ENT_QUOTES, 'UTF-8');
                if ($this->isValidLanguageCode($langParam)) {
                    $lang = $langParam;
                }
            }
        } elseif ($this->multiLangMethod === 'cookie') {
            // Check if the language is set in a cookie
            if (isset($_COOKIE[$this->multiLangKey])) {
                $langParam = htmlspecialchars($_COOKIE[$this->multiLangKey], ENT_QUOTES, 'UTF-8');
                if ($this->isValidLanguageCode($langParam)) {
                    $lang = $langParam;
                }
            }
        }

        return $lang;
    }

    /**
     * Load language file for the specified language code.
     *
     * @param string $lang
     * @throws Exception if the language file cannot be loaded.
     * @return void
     */
    private function loadLanguageFile($lang)
    {
        if (!$this->isValidLanguageCode($lang)) {
            // Invalid language code, load default language
            $lang = $this->defaultLanguage;
        }

        $langFile = $this->languageDir . $lang . '.json';

        if (!file_exists($langFile)) {
            // Language file not found, load default language file
            $langFile = $this->languageDir . $this->defaultLanguage . '.json';
        }

        $strings = json_decode(file_get_contents($langFile), true);

        if ($strings === null) {
            throw new Exception("Error loading language file: $langFile");
        }

        $this->langStrings = $strings;
    }

    /**
     * Change the current language.
     *
     * @param string $lang
     * @return bool
     */
    public function setLanguage($lang)
    {
        if (!$this->isValidLanguageCode($lang)) {
            return false;
        }

        if ($this->multiLangMethod === 'url') {
            // Redirect to the same page with the new language parameter
            $url = strtok($_SERVER["REQUEST_URI"], '?'); // Get the current URL without query string
            $queryParams = $_GET;
            $queryParams[$this->multiLangKey] = $lang; // Set the new language
            $newQueryStr = http_build_query($queryParams);
            header("Location: " . $url . '?' . $newQueryStr);
            exit;
        } elseif ($this->multiLangMethod === 'cookie') {
            // Set the language in a cookie
            setcookie($this->multiLangKey, $lang, time() + (365 * 86400), "/"); // 1 year
            // Update current language
            $this->currentLang = $lang;
            $this->loadLanguageFile($lang);
            return true;
        }
        return false;
    }

    /**
     * Get the list of available languages.
     *
     * @return array
     */
    public function getAvailableLanguages()
    {
        return array_values($this->availableLanguages);
    }

    /**
     * Get the current language code.
     *
     * @return string
     */
    public function getCurrentLanguageCode()
    {
        return $this->currentLang;
    }

    /**
     * Get a translated string.
     *
     * @param string $key
     * @param array $placeholders Optional placeholders.
     * @return string
     */
    public function translate($key, $placeholders = [])
    {
        if (isset($this->langStrings[$key])) {
            $translation = $this->langStrings[$key];
            // Replace placeholders
            if (!empty($placeholders)) {
                foreach ($placeholders as $placeholder => $value) {
                    $translation = str_replace('{' . $placeholder . '}', $value, $translation);
                }
            }
            return $translation;
        }

        // Attempt to fallback to default language if key is missing
        if ($this->currentLang !== $this->defaultLanguage) {
            $defaultStrings = $this->loadDefaultLanguageStrings();
            if (isset($defaultStrings[$key])) {
                $translation = $defaultStrings[$key];
                // Replace placeholders
                if (!empty($placeholders)) {
                    foreach ($placeholders as $placeholder => $value) {
                        $translation = str_replace('{' . $placeholder . '}', $value, $translation);
                    }
                }
                return $translation;
            }
        }

        return '<strong>{'.$key.'}</strong>'; // Return the key itself if no translation is found
    }

    /**
     * Load the default language strings.
     *
     * @return array
     */
    private function loadDefaultLanguageStrings()
    {
        static $defaultStrings = null;

        if ($defaultStrings === null) {
            $defaultLangFile = $this->languageDir . $this->defaultLanguage . '.json';
            if (file_exists($defaultLangFile)) {
                $defaultStrings = json_decode(file_get_contents($defaultLangFile), true);
            } else {
                $defaultStrings = [];
            }
        }

        return $defaultStrings;
    }

    /**
     * Get current language information (code, name, isRTL).
     *
     * @return array|null
     */
    public function getCurrentLanguageInfo()
    {
        if (isset($this->availableLanguages[$this->currentLang])) {
            return $this->availableLanguages[$this->currentLang];
        }
        return null;
    }

    /**
     * Check if the current language is RTL (right-to-left).
     *
     * @return bool
     */
    public function isCurrentLanguageRTL()
    {
        $langInfo = $this->getCurrentLanguageInfo();
        if ($langInfo && isset($langInfo['isRTL'])) {
            return $langInfo['isRTL'];
        }
        return false;
    }

    
}


