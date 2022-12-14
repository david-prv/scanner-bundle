<?php

/**
 * Class App
 *
 * <p>
 * This class is responsible for rendering the app view
 * of this project.
 * </p>
 *
 * <p>
 * All necessary configurations can be made here.
 * No other file has to be changed.
 * </p>
 *
 * @author David Dewes <hello@david-dewes.de>
 */
class App
{
    private Pages $pages;
    private Routes $routes;

    /**
     * App constructor.
     */
    function __construct()
    {
        spl_autoload_register(Autoloader::getInstance()->getLoader());

        $this->pages = Pages::getInstance();
        $this->routes = Routes::getInstance();

        $this->registerPages();
        $this->registerRoutes();
    }

    /**
     * Manages all routes.
     *
     * <p>
     * Usually you don't need to modify the handles
     * array, except you know what you do (e.g adding
     * entirely new functionality to the framework).
     * </p>
     *
     * <p>
     * HowTo:
     * -    Add a new entry to the returned array (order does not matter)
     * -    Use the scheme: "page_name" => function() { ... }
     * -    For the handle function use $this->core, followed by all
     *      specifications you want to make
     * </p>
     */
    private function registerRoutes(): void
    {
        $this->routes->add("page", function () {
            Core::getInstance()
                ->withParams($_GET)
                ->render();
        });

        $this->routes->add("run", function () {
            Core::getInstance()
                ->withParams($_GET)
                ->scan();
        });

        $this->routes->add("upload", function () {
            Core::getInstance()
                ->withParams($_POST)
                ->integrate();
        });

        $this->routes->add("delete",function () {
            Core::getInstance()
                ->withParams($_GET)
                ->delete();
        });

        $this->routes->add("edit", function () {
            Core::getInstance()
                ->withParams($_GET)
                ->edit();
        });

        $this->routes->add("schedule", function () {
            Core::getInstance()
                ->withParams($_GET)
                ->schedule();
        });

        $this->routes->add("pdf", function () {
            Core::getInstance()
                ->withParams($_GET)
                ->pdf();
        });
    }

    /**
     * Manages all pages
     *
     * <p>
     * Use this method to add new pages to the framework.
     * Please make sure, that the file in /views/, named by the page name
     * in lower-case letters, has to exist. Otherwise, the View cannot be
     * successfully rendered.
     * </p>
     *
     * <p>
     * HowTo:
     * -    Add a new line (order does not matter)
     * -    Write $this->add( ... );
     * -    Fill in all parameters, like name and placeholders
     * -    You can use the public programming interface from Core
     *      by using the local reference: $this->core->...
     * </p>
     */
    private function registerPages()
    {
        $this->pages->add("BASE", array(
            array("%TOOLS_LIST%", Core::getInstance()->renderToolsAsHtml()),
            array("%PROJECT_NAME%", Core::getInstance()->getProjectName()),
            array("%PROJECT_VERSION%", Core::getInstance()->getProjectVersion()),
            array("%PROJECT_AUTHOR%", Core::getInstance()->getProjectAuthor()),
            array("%PROJECT_DESCRIPTION%", Core::getInstance()->getProjectDescription()),
            array("%TOOLS_JSON%", Core::getInstance()->getToolsJson())
        ));

        $this->pages->add("SCHEDULE", array(
            array("%PROJECT_NAME%", Core::getInstance()->getProjectName()),
            array("%INTERACTIONS_LIST%", Core::getInstance()->renderScheduleAsHtml(Core::getInstance()->getArg("edit"))),
            array("%ID%", Core::getInstance()->getArg("edit"))
        ));

        $this->pages->add("INTEGRATE", array(
            array("%PROJECT_NAME%", Core::getInstance()->getProjectName())
        ));

        $this->pages->add("TEST", array(
            array("%PROJECT_NAME%", Core::getInstance()->getProjectName())
        ));
    }

    /** @return closure */
    private function getHandle(): closure
    {
        $default = $this->routes->default();

        $key = (count(array_keys($_GET)) >= 1) ? array_keys($_GET)[0] : -1;
        return $this->routes->get(strtolower($key)) ?? $default;
    }

    /** Run */
    public function run(): void
    {
        $handle = $this->getHandle();
        $handle();
    }
}