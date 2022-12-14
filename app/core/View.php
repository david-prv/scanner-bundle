<?php

/**
 * Class View
 *
 * <p>
 * This class handles the proper building of a view.
 * A view is an abstraction layer for UI generation.
 * It can contain all essential ingredients a normal html page can have.
 * </p>
 *
 * @author David Dewes <hello@david-dewes.de>
 */
class View
{
    private string $filename;
    private string $viewPath;
    private array $placeholders;
    private bool $error;

    /**
     * View constructor.
     *
     * @param string $viewPath
     */
    function __construct(string $viewPath)
    {
        $this->viewPath = $viewPath;
        $this->error = false;
    }

    /**
     * Renders a View object
     *
     * @param View $view
     * @return bool
     */
    public static function render(View $view): bool
    {
        if (!$view->isComplete()) {
            die("View could not be constructed");
        }

        $html = file_get_contents($view->getViewPath() . "/" . $view->getFileName());

        foreach ($view->getPlaceholders() as $key => $value) {
            $placeholder = (string)$value[0];
            $realValue = (string)$value[1];
            $html = str_replace($placeholder, $realValue, $html);
        }

        print_r($html);

        return $html != false && $html != NULL;
    }

    /**
     * Defines which template file should be used
     *
     * @param string $template
     * @return View
     */
    public function setTemplate(string $template): View
    {
        $this->filename = strtolower($template) . ".htm";
        return $this;
    }

    /**
     * Defines the two-dimensional array of placeholders
     * and their corresponding values. Placeholders can be expressed in a template
     * with a percentage symbol, followed by the placeholder name and then
     * closed with another percentage symbol
     *
     * @param array $placeholders
     * @return View
     */
    public function setPlaceholders(array $placeholders): View
    {
        $this->placeholders = $placeholders;
        return $this;
    }

    /**
     * Indicates that an error occurred in the caller.
     * Prevents the render method to perform
     *
     * @param bool $value
     * @return View
     */
    public function setError(bool $value): View
    {
        $this->error = $value;
        return $this;
    }

    /**
     * Returns the location of the views
     *
     * @return string
     */
    public function getViewPath(): string
    {
        return $this->viewPath;
    }

    /**
     * Returns the placeholders
     *
     * @return array
     */
    public function getPlaceholders(): array
    {
        return $this->placeholders;
    }

    /**
     * Returns the concrete filename of the
     * used template
     *
     * @return string
     */
    public function getFileName(): string
    {
        return $this->filename;
    }

    /**
     * Returns whether an error occurred in the caller
     * or not, what means analogously if the view was constructed
     * successfully or not
     *
     * @return bool
     */
    public function isComplete(): bool
    {
        return !$this->error;
    }
}