<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ScanHardcodedTexts extends Command
{
    protected $signature = 'scan:hardcoded-texts
                            {--output=storage/hardcoded_texts.json : Output file path}
                            {--format=json : Output format (json, csv, txt)}
                            {--min-length=2 : Minimum text length to consider}
                            {--blade-only : Scan only Blade files}
                            {--js-only : Scan only JS/Vue files}
                            {--php-only : Scan only PHP files}';

    protected $description = 'Scan all templates (Blade, JS, Vue) and controllers for hardcoded texts that should be translated';

    // Mots/patterns à ignorer (faux positifs communs)
    protected array $ignoredPatterns = [
        // Classes CSS, IDs, attributs HTML
        '~^[a-z0-9_-]+$~i', // Identifiants simples sans espaces
        '~^#[a-fA-F0-9]{3,8}$~', // Couleurs hex
        '~^(btn|col|row|d-|mt-|mb-|pt-|pb-|px-|py-|mx-|my-|text-|bg-|border-|rounded|flex|grid|hidden|block|inline|absolute|relative|fixed|sticky|overflow|w-|h-|min-|max-|gap-|space-|justify-|items-|self-|order-|font-|leading-|tracking-|whitespace-|break-|align-|underline|line-through|no-underline|uppercase|lowercase|capitalize|normal-case|italic|not-italic|antialiased|subpixel-antialiased|truncate|opacity-|shadow|ring|transition|duration|ease|delay|animate|cursor-|select-|resize|appearance|outline|fill-|stroke-|sr-only|not-sr-only)~', // Classes Tailwind/Bootstrap
        '~^(fa|fas|far|fab|fal|fad|bi|icon|mdi|material-icons)~', // Icon classes
        '~^(sm|md|lg|xl|xxl|xs|2xl|3xl|4xl|5xl)~', // Breakpoints
        '~^(GET|POST|PUT|PATCH|DELETE|HEAD|OPTIONS)$~', // HTTP methods
        '~^(ASC|DESC|asc|desc)$~', // SQL ordering
        '~^(true|false|null|undefined|NaN)$~i', // Literals
        '~^[0-9.,\s%$]+$~', // Numbers, currencies
        '~^\s*$~', // Empty or whitespace only
        '~^[!@#%^&*()\-_+=\[\]{}|\\\\:";\'<>?,.`]+$~', // Special chars only
        '~^(https?|ftp|mailto|tel|file)://~', // URLs
        '~^/[\w/\-]*$~', // URL paths
        '~^[\w.\-]+@[\w.\-]+\.\w+$~', // Emails
        '~^application/(json|xml|pdf|octet-stream)~', // MIME types
        '~^(image|video|audio|text)/~', // MIME types
        '~^[A-Z][A-Z0-9_]+$~', // CONSTANTS
        '~^\d{4}-\d{2}-\d{2}~', // Dates ISO
        '~^Y-m-d$~', // Date formats
        '~^d/m/Y$~', // Date formats
        '~^m/d/Y$~', // Date formats
        '~^[a-z]+\.[a-z]+~', // Possible translation keys like 'messages.key'
        // Laravel validation rules
        '~^(required|nullable|string|integer|numeric|boolean|array|date|email|min|max|between|in|exists|unique|confirmed|same|different|regex|image|file|mimes|mimetypes|dimensions|size|sometimes|bail|accepted|active_url|after|before|alpha|alpha_num|alpha_dash|digits|digits_between|filled|gt|gte|lt|lte|ip|ipv4|ipv6|json|not_in|not_regex|present|prohibited|url|uuid)(\||$|:)~',
        '~^(required|nullable)\|(string|integer|array|boolean|numeric|date|email|image|file)\|~',
        '~\|max:\d+$~',
    ];

    // Extensions de fichiers qui sont probablement des traductions
    protected array $translationPatterns = [
        '/__\s*\(\s*[\'"](.+?)[\'"]\s*\)/', // __('text')
        '/trans\s*\(\s*[\'"](.+?)[\'"]\s*\)/', // trans('text')
        '/trans_choice\s*\(\s*[\'"](.+?)[\'"]\s*,/', // trans_choice('text', n)
        '/@lang\s*\(\s*[\'"](.+?)[\'"]\s*\)/', // @lang('text')
        '/Lang::get\s*\(\s*[\'"](.+?)[\'"]\s*\)/', // Lang::get('text')
        '/\{\{\s*\$t\s*\(\s*[\'"](.+?)[\'"]\s*\)\s*\}\}/', // {{ $t('text') }} Vue
        '/\$t\s*\(\s*[\'"](.+?)[\'"]\s*\)/', // $t('text') Vue
        '/i18n\.t\s*\(\s*[\'"](.+?)[\'"]\s*\)/', // i18n.t('text')
    ];

    // Attributs HTML où le texte est attendu
    protected array $textAttributes = [
        'title', 'alt', 'placeholder', 'aria-label', 'aria-describedby',
        'data-confirm', 'data-title', 'data-message', 'data-tooltip',
        'label', 'value' // value peut contenir du texte visible
    ];

    protected array $results = [];
    protected int $totalFound = 0;

    public function handle(): int
    {
        $this->info('===========================================');
        $this->info('  SCAN DES TEXTES EN DUR - KABAS');
        $this->info('===========================================');
        $this->newLine();

        $startTime = microtime(true);

        $bladeOnly = $this->option('blade-only');
        $jsOnly = $this->option('js-only');
        $phpOnly = $this->option('php-only');
        $scanAll = !$bladeOnly && !$jsOnly && !$phpOnly;

        // Scanner les fichiers Blade
        if ($scanAll || $bladeOnly) {
            $this->info('Scanning Blade templates...');
            $this->scanBladeFiles();
        }

        // Scanner les fichiers JS/Vue
        if ($scanAll || $jsOnly) {
            $this->info('Scanning JS/Vue files...');
            $this->scanJsVueFiles();
        }

        // Scanner les contrôleurs PHP
        if ($scanAll || $phpOnly) {
            $this->info('Scanning PHP Controllers...');
            $this->scanPhpControllers();
        }

        // Générer le rapport
        $this->generateReport();

        $duration = round(microtime(true) - $startTime, 2);

        $this->newLine();
        $this->info('===========================================');
        $this->info("  SCAN TERMINE en {$duration}s");
        $this->info("  Total: {$this->totalFound} textes potentiellement en dur");
        $this->info('===========================================');

        return Command::SUCCESS;
    }

    protected function scanBladeFiles(): void
    {
        $bladePath = resource_path('views');
        $files = $this->getFilesRecursively($bladePath, ['blade.php']);

        $bar = $this->output->createProgressBar(count($files));
        $bar->start();

        foreach ($files as $file) {
            $this->scanBladeFile($file);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    protected function scanBladeFile(string $filePath): void
    {
        $content = File::get($filePath);
        $lines = explode("\n", $content);
        $relativePath = $this->getRelativePath($filePath);

        foreach ($lines as $lineNumber => $line) {
            $actualLineNumber = $lineNumber + 1;

            // Ignorer les lignes qui sont déjà des traductions
            if ($this->isTranslatedLine($line)) {
                continue;
            }

            // Ignorer les commentaires Blade
            if (preg_match('/^\s*\{\{--.*--\}\}\s*$/', $line)) {
                continue;
            }

            // Chercher le texte entre les balises HTML
            $this->findHtmlTextContent($line, $relativePath, $actualLineNumber);

            // Chercher le texte dans les attributs
            $this->findAttributeText($line, $relativePath, $actualLineNumber);

            // Chercher les chaînes PHP en dur dans Blade
            $this->findBladePhpStrings($line, $relativePath, $actualLineNumber);
        }
    }

    protected function findHtmlTextContent(string $line, string $filePath, int $lineNumber): void
    {
        // Texte entre balises HTML: >Text<
        // Éviter les balises qui contiennent des variables Blade
        preg_match_all('/>([^<>{}\$@]+)</u', $line, $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches[1] as $match) {
            $text = trim($match[0]);
            if ($this->isValidHardcodedText($text)) {
                $this->addResult($filePath, $lineNumber, $text, 'blade_html', $line);
            }
        }

        // Texte après > sans < (fin de ligne ou suite)
        // Ex: <label>Mon texte
        if (preg_match('/>([^<>{}\$@]+)$/u', $line, $match)) {
            $text = trim($match[1]);
            if ($this->isValidHardcodedText($text)) {
                $this->addResult($filePath, $lineNumber, $text, 'blade_html_end', $line);
            }
        }

        // Texte au début avant < (continuation)
        // Ex: Mon texte</label>
        if (preg_match('/^([^<>{}\$@]+)</u', trim($line), $match)) {
            $text = trim($match[1]);
            // Éviter les faux positifs avec les commentaires HTML
            if ($this->isValidHardcodedText($text) && !Str::startsWith($text, '--')) {
                $this->addResult($filePath, $lineNumber, $text, 'blade_html_start', $line);
            }
        }
    }

    protected function findAttributeText(string $line, string $filePath, int $lineNumber): void
    {
        foreach ($this->textAttributes as $attr) {
            // Attribut avec guillemets doubles
            $pattern = '/' . preg_quote($attr, '/') . '\s*=\s*"([^"{\$]+)"/u';
            preg_match_all($pattern, $line, $matches);
            foreach ($matches[1] as $text) {
                $text = trim($text);
                if ($this->isValidHardcodedText($text)) {
                    $this->addResult($filePath, $lineNumber, $text, "blade_attr_{$attr}", $line);
                }
            }

            // Attribut avec guillemets simples
            $pattern = '/' . preg_quote($attr, '/') . "\s*=\s*'([^'{\$]+)'/u";
            preg_match_all($pattern, $line, $matches);
            foreach ($matches[1] as $text) {
                $text = trim($text);
                if ($this->isValidHardcodedText($text)) {
                    $this->addResult($filePath, $lineNumber, $text, "blade_attr_{$attr}", $line);
                }
            }
        }

        // Chercher data-confirm et autres attributs de confirmation
        preg_match_all('/onclick\s*=\s*["\'].*confirm\s*\(\s*[\'"]([^"\']+)[\'"]\s*\)/u', $line, $matches);
        foreach ($matches[1] as $text) {
            $text = trim($text);
            if ($this->isValidHardcodedText($text)) {
                $this->addResult($filePath, $lineNumber, $text, 'blade_confirm', $line);
            }
        }
    }

    protected function findBladePhpStrings(string $line, string $filePath, int $lineNumber): void
    {
        // Chaînes dans les directives Blade comme @if, @foreach, etc. avec des comparaisons de texte
        // Ex: @if($status == 'Active')
        preg_match_all('/@(?:if|elseif|unless|case)\s*\([^)]*[\'"]([A-Za-z\s]{3,})[\'"][^)]*\)/u', $line, $matches);
        foreach ($matches[1] as $text) {
            $text = trim($text);
            if ($this->isValidHardcodedText($text) && $this->containsReadableWords($text)) {
                $this->addResult($filePath, $lineNumber, $text, 'blade_directive_string', $line);
            }
        }

        // Texte dans les Echo Blade {{ }} qui ne sont pas des variables
        // Chercher {{ 'texte' }} ou {{ "texte" }}
        preg_match_all('/\{\{\s*[\'"]([^\'"]+)[\'"]\s*\}\}/u', $line, $matches);
        foreach ($matches[1] as $text) {
            $text = trim($text);
            if ($this->isValidHardcodedText($text)) {
                $this->addResult($filePath, $lineNumber, $text, 'blade_echo_string', $line);
            }
        }
    }

    protected function scanJsVueFiles(): void
    {
        $jsPath = resource_path('js');
        $files = $this->getFilesRecursively($jsPath, ['js', 'vue', 'ts', 'jsx', 'tsx']);

        $bar = $this->output->createProgressBar(count($files));
        $bar->start();

        foreach ($files as $file) {
            $this->scanJsVueFile($file);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    protected function scanJsVueFile(string $filePath): void
    {
        $content = File::get($filePath);
        $lines = explode("\n", $content);
        $relativePath = $this->getRelativePath($filePath);

        $inTemplateSection = false;
        $inScriptSection = false;

        foreach ($lines as $lineNumber => $line) {
            $actualLineNumber = $lineNumber + 1;

            // Ignorer les commentaires
            $trimmedLine = trim($line);
            if (Str::startsWith($trimmedLine, '//') || Str::startsWith($trimmedLine, '*') || Str::startsWith($trimmedLine, '/*')) {
                continue;
            }

            // Détecter les sections Vue
            if (preg_match('/<template\b/i', $line)) {
                $inTemplateSection = true;
            }
            if (preg_match('/<\/template>/i', $line)) {
                $inTemplateSection = false;
            }
            if (preg_match('/<script\b/i', $line)) {
                $inScriptSection = true;
            }
            if (preg_match('/<\/script>/i', $line)) {
                $inScriptSection = false;
            }

            // Ignorer les lignes déjà traduites
            if ($this->isTranslatedLine($line)) {
                continue;
            }

            // Dans la section template Vue, chercher comme HTML
            if ($inTemplateSection) {
                $this->findHtmlTextContent($line, $relativePath, $actualLineNumber);
                $this->findVueAttributeText($line, $relativePath, $actualLineNumber);
            }

            // Dans la section script ou fichier JS pur
            if ($inScriptSection || Str::endsWith($filePath, '.js') || Str::endsWith($filePath, '.ts')) {
                $this->findJsStrings($line, $relativePath, $actualLineNumber);
            }
        }
    }

    protected function findVueAttributeText(string $line, string $filePath, int $lineNumber): void
    {
        // Attributs Vue avec texte en dur (sans :)
        // Ex: placeholder="Enter text" mais pas :placeholder="variable"
        foreach ($this->textAttributes as $attr) {
            // Éviter les bindings Vue (:attr ou v-bind:attr)
            $pattern = '/(?<!:)\b' . preg_quote($attr, '/') . '\s*=\s*"([^"{\$]+)"/u';
            preg_match_all($pattern, $line, $matches);
            foreach ($matches[1] as $text) {
                $text = trim($text);
                if ($this->isValidHardcodedText($text)) {
                    $this->addResult($filePath, $lineNumber, $text, "vue_attr_{$attr}", $line);
                }
            }
        }
    }

    protected function findJsStrings(string $line, string $filePath, int $lineNumber): void
    {
        // Ignorer les imports et requires
        if (preg_match('/^\s*(import|export|require|from)\b/', $line)) {
            return;
        }

        // Chercher les chaînes qui ressemblent à du texte utilisateur
        // alert(), confirm(), console.log avec texte
        $patterns = [
            '/alert\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/u',
            '/confirm\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/u',
            '/console\.(log|warn|error)\s*\(\s*[\'"]([^\'"]+)[\'"]/u',
            '/\.text\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/u',
            '/\.html\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/u',
            '/\.append\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/u',
            '/\.prepend\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/u',
            '/innerHTML\s*=\s*[\'"]([^\'"]+)[\'"]/u',
            '/textContent\s*=\s*[\'"]([^\'"]+)[\'"]/u',
            '/placeholder:\s*[\'"]([^\'"]+)[\'"]/u',
            '/title:\s*[\'"]([^\'"]+)[\'"]/u',
            '/message:\s*[\'"]([^\'"]+)[\'"]/u',
            '/label:\s*[\'"]([^\'"]+)[\'"]/u',
            '/text:\s*[\'"]([^\'"]+)[\'"]/u',
            '/toast\s*\(\s*[\'"]([^\'"]+)[\'"]/u',
            '/showToast\s*\(\s*[\'"]([^\'"]+)[\'"]/u',
            '/notify\s*\(\s*[\'"]([^\'"]+)[\'"]/u',
            '/Swal\.(fire|alert)\s*\(\s*\{[^}]*title:\s*[\'"]([^\'"]+)[\'"]/u',
            '/Swal\.(fire|alert)\s*\(\s*\{[^}]*text:\s*[\'"]([^\'"]+)[\'"]/u',
        ];

        foreach ($patterns as $pattern) {
            preg_match_all($pattern, $line, $matches);
            $textIndex = count($matches) - 1; // Le dernier groupe de capture contient le texte
            foreach ($matches[$textIndex] as $text) {
                $text = trim($text);
                if ($this->isValidHardcodedText($text) && $this->containsReadableWords($text)) {
                    $this->addResult($filePath, $lineNumber, $text, 'js_string', $line);
                }
            }
        }

        // Chercher les assignations de variables avec du texte visible
        // Ex: const message = "Votre commande a été validée"
        preg_match_all('/(const|let|var)\s+\w+\s*=\s*[\'"]([^\'"]{5,})[\'"]/', $line, $matches);
        foreach ($matches[2] as $text) {
            $text = trim($text);
            if ($this->isValidHardcodedText($text) && $this->containsReadableWords($text) && $this->looksLikeUserFacingText($text)) {
                $this->addResult($filePath, $lineNumber, $text, 'js_variable', $line);
            }
        }

        // Template literals avec du texte
        preg_match_all('/`([^`\$]+)`/', $line, $matches);
        foreach ($matches[1] as $text) {
            $text = trim($text);
            if ($this->isValidHardcodedText($text) && strlen($text) > 10 && $this->containsReadableWords($text) && $this->looksLikeUserFacingText($text)) {
                $this->addResult($filePath, $lineNumber, $text, 'js_template_literal', $line);
            }
        }
    }

    protected function scanPhpControllers(): void
    {
        $controllerPath = app_path('Http/Controllers');
        $files = $this->getFilesRecursively($controllerPath, ['php']);

        // Ajouter aussi les helpers et autres fichiers PHP importants
        $helperPath = app_path('Helpers');
        if (File::isDirectory($helperPath)) {
            $files = array_merge($files, $this->getFilesRecursively($helperPath, ['php']));
        }

        $bar = $this->output->createProgressBar(count($files));
        $bar->start();

        foreach ($files as $file) {
            $this->scanPhpFile($file);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    protected function scanPhpFile(string $filePath): void
    {
        $content = File::get($filePath);
        $lines = explode("\n", $content);
        $relativePath = $this->getRelativePath($filePath);

        foreach ($lines as $lineNumber => $line) {
            $actualLineNumber = $lineNumber + 1;

            // Ignorer les lignes déjà traduites
            if ($this->isTranslatedLine($line)) {
                continue;
            }

            // Ignorer les commentaires
            $trimmedLine = trim($line);
            if (Str::startsWith($trimmedLine, '//') || Str::startsWith($trimmedLine, '*') || Str::startsWith($trimmedLine, '/*') || Str::startsWith($trimmedLine, '#')) {
                continue;
            }

            // Ignorer les use, namespace, class declarations
            if (preg_match('/^\s*(use|namespace|class|interface|trait|abstract|final|private|protected|public|function)\b/', $line)) {
                continue;
            }

            $this->findPhpStrings($line, $relativePath, $actualLineNumber);
        }
    }

    protected function findPhpStrings(string $line, string $filePath, int $lineNumber): void
    {
        // Messages flash et redirections
        $patterns = [
            '/->with\s*\(\s*[\'"](?:success|error|warning|info|message|status)[\'"]\s*,\s*[\'"]([^\'"]+)[\'"]\s*\)/u',
            '/->withErrors\s*\(\s*\[?\s*[\'"]([^\'"]+)[\'"]/u',
            '/session\s*\(\s*\)\s*->\s*flash\s*\(\s*[\'"][^\'"]+[\'"]\s*,\s*[\'"]([^\'"]+)[\'"]\s*\)/u',
            '/Session::flash\s*\(\s*[\'"][^\'"]+[\'"]\s*,\s*[\'"]([^\'"]+)[\'"]\s*\)/u',
            '/flash\s*\(\s*[\'"][^\'"]+[\'"]\s*,\s*[\'"]([^\'"]+)[\'"]\s*\)/u',
            '/abort\s*\(\s*\d+\s*,\s*[\'"]([^\'"]+)[\'"]\s*\)/u',
            '/throw\s+new\s+\w*Exception\s*\(\s*[\'"]([^\'"]+)[\'"]/u',
            '/response\s*\(\s*\)\s*->\s*json\s*\(\s*\[\s*[\'"]message[\'"]\s*=>\s*[\'"]([^\'"]+)[\'"]/u',
            '/return\s+response\s*\(\s*[\'"]([^\'"]+)[\'"]/u',
            '/->back\s*\(\s*\)\s*->\s*with\s*\(\s*[\'"][^\'"]+[\'"]\s*,\s*[\'"]([^\'"]+)[\'"]\s*\)/u',
            '/redirect\s*\([^)]*\)\s*->\s*with\s*\(\s*[\'"][^\'"]+[\'"]\s*,\s*[\'"]([^\'"]+)[\'"]\s*\)/u',
        ];

        foreach ($patterns as $pattern) {
            preg_match_all($pattern, $line, $matches);
            foreach ($matches[1] as $text) {
                $text = trim($text);
                if ($this->isValidHardcodedText($text) && $this->containsReadableWords($text)) {
                    $this->addResult($filePath, $lineNumber, $text, 'php_message', $line);
                }
            }
        }

        // Validation messages
        preg_match_all('/[\'"](\w+)\.(\w+)[\'"]\s*=>\s*[\'"]([^\'"]+)[\'"]/', $line, $matches);
        foreach ($matches[3] as $text) {
            $text = trim($text);
            if ($this->isValidHardcodedText($text) && $this->containsReadableWords($text)) {
                $this->addResult($filePath, $lineNumber, $text, 'php_validation', $line);
            }
        }

        // Texte de boutons, labels dans les arrays retournés à la vue
        $arrayPatterns = [
            '/[\'"](label|title|message|text|placeholder|description|name|button|submit|error|success|warning|info|header|footer|content|tooltip|hint|help)[\'"]\s*=>\s*[\'"]([^\'"]+)[\'"]/i',
        ];

        foreach ($arrayPatterns as $pattern) {
            preg_match_all($pattern, $line, $matches);
            foreach ($matches[2] as $text) {
                $text = trim($text);
                if ($this->isValidHardcodedText($text) && $this->containsReadableWords($text)) {
                    $this->addResult($filePath, $lineNumber, $text, 'php_array_value', $line);
                }
            }
        }

        // Mail subjects et contenu
        preg_match_all('/->subject\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/u', $line, $matches);
        foreach ($matches[1] as $text) {
            $text = trim($text);
            if ($this->isValidHardcodedText($text)) {
                $this->addResult($filePath, $lineNumber, $text, 'php_mail_subject', $line);
            }
        }
    }

    protected function isTranslatedLine(string $line): bool
    {
        foreach ($this->translationPatterns as $pattern) {
            if (preg_match($pattern, $line)) {
                return true;
            }
        }

        // Vérifier aussi les patterns partiels
        if (preg_match('/__\s*\(/', $line) ||
            preg_match('/trans\s*\(/', $line) ||
            preg_match('/@lang\s*\(/', $line) ||
            preg_match('/\$t\s*\(/', $line) ||
            preg_match('/Lang::get\s*\(/', $line)) {
            return true;
        }

        return false;
    }

    protected function isValidHardcodedText(string $text): bool
    {
        // Longueur minimale
        $minLength = (int) $this->option('min-length');
        if (strlen(trim($text)) < $minLength) {
            return false;
        }

        // Vérifier les patterns à ignorer
        foreach ($this->ignoredPatterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return false;
            }
        }

        // Ignorer les valeurs qui ressemblent à des clés de config/routes
        if (preg_match('/^[a-z_]+\.[a-z_]+(\.[a-z_]+)*$/i', $text)) {
            return false;
        }

        // Ignorer les noms de fichiers
        if (preg_match('/\.(php|js|css|vue|blade|html|json|xml|md|txt|pdf|jpg|jpeg|png|gif|svg|webp)$/i', $text)) {
            return false;
        }

        // Ignorer si c'est un tag HTML seul
        if (preg_match('/^<\/?[a-z]+\s*\/?>$/i', $text)) {
            return false;
        }

        // Ignorer le code PHP/JS - appels de méthodes, propriétés, etc.
        if (preg_match('/^[a-zA-Z_]+\s*\([^)]*\);?$/', $text)) {
            return false;
        }
        if (preg_match('/^[a-zA-Z_]+\s*\)$/', $text)) { // Fin de parenthèse
            return false;
        }
        if (preg_match('/^\$[a-zA-Z_]+/', $text)) { // Variables PHP
            return false;
        }
        if (preg_match('/^->[a-zA-Z_]+/', $text)) { // Appels de méthodes
            return false;
        }
        if (preg_match('/^[a-zA-Z_]+;$/', $text)) { // Fin d'instruction
            return false;
        }
        if (preg_match('/\(\s*\)/', $text)) { // Appels de fonctions vides ()
            return false;
        }
        if (preg_match('/^(collect|array|fn|function)\b/', $text)) { // Mots-clés PHP
            return false;
        }
        if (preg_match('/^\d+\)$/', $text)) { // "0)" et similaires
            return false;
        }
        if (preg_match('/^\'[^\']+\'$/', $text)) { // Chaînes entre quotes simples
            return false;
        }
        if (preg_match('/keyBy|isEmpty|count|sum|map|filter|first|last|where|pluck/', $text)) { // Méthodes Laravel Collection
            return false;
        }
        if (preg_match('/^[a-z_]+\s*=/', $text)) { // Assignations
            return false;
        }

        return true;
    }

    protected function containsReadableWords(string $text): bool
    {
        // Doit contenir au moins un mot de 3+ lettres
        return preg_match('/[a-zA-ZÀ-ÿ]{3,}/', $text) === 1;
    }

    protected function looksLikeUserFacingText(string $text): bool
    {
        // Doit contenir des espaces ou être un mot significatif
        // Ignorer les identifiants techniques
        if (preg_match('/^[a-z_]+$/i', $text)) {
            return false;
        }

        // Doit avoir au moins un espace ou une majuscule au milieu
        if (strpos($text, ' ') !== false) {
            return true;
        }

        // Première lettre majuscule suivie de minuscules = probablement un mot
        if (preg_match('/^[A-ZÀ-Ÿ][a-zà-ÿ]+$/', $text) && strlen($text) > 4) {
            return true;
        }

        // Contient des caractères français accentués
        if (preg_match('/[àâäéèêëïîôùûüÿçœæ]/i', $text)) {
            return true;
        }

        return false;
    }

    protected function addResult(string $file, int $line, string $text, string $type, string $fullLine): void
    {
        // Éviter les doublons
        $key = md5($file . $line . $text);

        if (!isset($this->results[$key])) {
            $this->results[$key] = [
                'file' => $file,
                'line' => $line,
                'text' => $text,
                'type' => $type,
                'context' => trim(Str::limit($fullLine, 200)),
                'suggested_key' => $this->generateSuggestedKey($text),
            ];
            $this->totalFound++;
        }
    }

    protected function generateSuggestedKey(string $text): string
    {
        // Générer une clé de traduction suggérée
        $key = Str::slug($text, '_');
        $key = Str::limit($key, 50, '');

        // Nettoyer
        $key = preg_replace('/_+/', '_', $key);
        $key = trim($key, '_');

        return $key ?: 'text_' . substr(md5($text), 0, 8);
    }

    protected function getFilesRecursively(string $path, array $extensions): array
    {
        if (!File::isDirectory($path)) {
            return [];
        }

        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                foreach ($extensions as $ext) {
                    if (Str::endsWith($file->getFilename(), ".{$ext}")) {
                        $files[] = $file->getPathname();
                        break;
                    }
                }
            }
        }

        return $files;
    }

    protected function getRelativePath(string $fullPath): string
    {
        return str_replace(base_path() . '/', '', $fullPath);
    }

    protected function generateReport(): void
    {
        $format = $this->option('format');
        $outputPath = $this->option('output');

        // Si le chemin est absolu, l'utiliser tel quel, sinon le considérer relatif à base_path
        if (Str::startsWith($outputPath, '/')) {
            $fullPath = $outputPath;
        } else {
            $fullPath = base_path($outputPath);
        }

        // S'assurer que le répertoire existe
        $directory = dirname($fullPath);
        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        // Trier les résultats par fichier puis par ligne
        $sortedResults = collect($this->results)->sortBy([
            ['file', 'asc'],
            ['line', 'asc'],
        ])->values()->all();

        switch ($format) {
            case 'csv':
                $this->generateCsvReport($fullPath, $sortedResults);
                break;
            case 'txt':
                $this->generateTxtReport($fullPath, $sortedResults);
                break;
            case 'json':
            default:
                $this->generateJsonReport($fullPath, $sortedResults);
                break;
        }

        $this->info("Report generated: {$outputPath}");
    }

    protected function generateJsonReport(string $path, array $results): void
    {
        $report = [
            'generated_at' => now()->toIso8601String(),
            'total_found' => $this->totalFound,
            'summary' => $this->generateSummary($results),
            'items' => $results,
        ];

        File::put($path, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    protected function generateCsvReport(string $path, array $results): void
    {
        $csv = "File,Line,Type,Text,Suggested Key,Context\n";

        foreach ($results as $item) {
            $csv .= sprintf(
                '"%s",%d,"%s","%s","%s","%s"' . "\n",
                $item['file'],
                $item['line'],
                $item['type'],
                str_replace('"', '""', $item['text']),
                $item['suggested_key'],
                str_replace('"', '""', $item['context'])
            );
        }

        File::put($path, $csv);
    }

    protected function generateTxtReport(string $path, array $results): void
    {
        $txt = "===========================================\n";
        $txt .= "  RAPPORT DES TEXTES EN DUR - KABAS\n";
        $txt .= "  Généré le: " . now()->format('Y-m-d H:i:s') . "\n";
        $txt .= "  Total: {$this->totalFound} éléments\n";
        $txt .= "===========================================\n\n";

        $currentFile = '';
        foreach ($results as $item) {
            if ($currentFile !== $item['file']) {
                $currentFile = $item['file'];
                $txt .= "\n### {$currentFile}\n";
                $txt .= str_repeat('-', strlen($currentFile) + 4) . "\n";
            }

            $txt .= sprintf(
                "  L%d [%s]: %s\n    Context: %s\n    Suggested key: %s\n\n",
                $item['line'],
                $item['type'],
                $item['text'],
                $item['context'],
                $item['suggested_key']
            );
        }

        File::put($path, $txt);
    }

    protected function generateSummary(array $results): array
    {
        $byType = [];
        $byFile = [];

        foreach ($results as $item) {
            $type = $item['type'];
            $file = $item['file'];

            if (!isset($byType[$type])) {
                $byType[$type] = 0;
            }
            $byType[$type]++;

            if (!isset($byFile[$file])) {
                $byFile[$file] = 0;
            }
            $byFile[$file]++;
        }

        arsort($byType);
        arsort($byFile);

        return [
            'by_type' => $byType,
            'by_file' => array_slice($byFile, 0, 20), // Top 20 fichiers
        ];
    }
}
