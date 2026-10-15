<?php
namespace verbb\formie\web\twig;

use Craft;
use craft\web\View;

use Twig\Error\LoaderError;
use Twig\Loader\LoaderInterface;
use Twig\Source;

/** Resolves HTML-field includes through site roots even during CP previews. */
class HtmlTemplateLoader implements LoaderInterface
{
    // Public Methods
    // =========================================================================

    public function __construct(private View $view)
    {
    }

    public function getSourceContext(string $name): Source
    {
        $path = $this->_resolve($name);
        $source = file_get_contents($path);

        if ($source === false) {
            throw new LoaderError('Unable to read the HTML field template.');
        }

        return new Source($source, $name, $path);
    }

    public function getCacheKey(string $name): string
    {
        return $this->_resolve($name);
    }

    public function isFresh(string $name, int $time): bool
    {
        return filemtime($this->_resolve($name)) <= $time;
    }

    public function exists(string $name): bool
    {
        try {
            $this->_resolve($name);
            return true;
        } catch (LoaderError) {
            return false;
        }
    }


    // Private Methods
    // =========================================================================

    private function _resolve(string $name): string
    {
        $name = str_replace('\\', '/', $name);

        // Validate before Craft removes a registered-root prefix from the name.
        if ($name === '' || str_starts_with($name, '/') || str_contains($name, ':') || str_contains($name, "\0") || in_array('..', explode('/', $name), true)) {
            throw new LoaderError('HTML field includes must name a site template.');
        }

        $path = $this->view->resolveTemplate($name, View::TEMPLATE_MODE_SITE);
        $resolved = $path === false ? false : realpath($path);
        $roots = [Craft::$app->getPath()->getSiteTemplatesPath()];

        foreach ($this->view->getSiteTemplateRoots() as $paths) {
            array_push($roots, ...(array)$paths);
        }

        // Resolve symlinks too; a file must stay inside a developer-configured root.
        foreach ($roots as $root) {
            $root = realpath(Craft::getAlias($root));

            if ($resolved !== false && $root !== false && str_starts_with($resolved, rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR) && is_readable($resolved)) {
                return $resolved;
            }
        }

        throw new LoaderError('Unable to find an HTML field template within the site template roots.');
    }
}
