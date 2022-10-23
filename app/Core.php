<?php

/**
 * Class Core
 *
 * <p>
 * This file is responsible for parsing the tools,
 * preparing them to show in the frontend, verifying their
 * integrity and finally issuing scan reports for the user.
 * </p>
 *
 * @author David Dewes <hello@david-dewes.de>
 */
class Core
{

    private static ?Core $instance = NULL;

    private ?array $argv = NULL;
    private $TOOLS_OBJECT;

    private string $APP_PATH;
    private string $VIEW_PATH;
    private string $TOOLS_PATH;

    private string $PROJECT_NAME = "WP Scanner Bundle";
    private string $PROJECT_AUTHOR = "David Dewes";
    private string $PROJECT_VERSION = "1.0.0";
    private string $PROJECT_DESCRIPTION =
        "A small collection of open-source tools out there to " .
        "inspect and scan any kind of wordpress page.";

    /**
     * Core constructor.
     *
     * @param NULL $tp
     * @param NULL $tip
     */
    private function __construct($tp = NULL, $tip = NULL)
    {
        $this->APP_PATH = getcwd();
        $this->VIEW_PATH = $this->APP_PATH . "/app/templates";
        $this->TOOLS_PATH = ($tp === NULL) ? $this->APP_PATH . "/app/tools" : $tp;
        $this->TOOLS_OBJECT = json_decode(file_get_contents(($tip === NULL) ? $this->APP_PATH . "/app/tools/map.json" : $tip), false);

        foreach ($this->TOOLS_OBJECT as $key => $value) {
            if ($value->ignore) unset($this->TOOLS_OBJECT[$key]);
        }
    }

    /**
     * Creates an Instance
     *
     * @return Core
     */
    public static function getInstance(): Core
    {
        if (self::$instance === NULL) {
            self::$instance = new Core();
        }
        return self::$instance;
    }

    /**
     * Sets the argv attribute and thus
     * allows the Core to use parameters passed as
     * GET or POST params
     *
     * @param $params
     * @return Core
     */
    public function withParams($params): Core
    {
        $this->argv = $params;
        return self::$instance;
    }

    /**
     * Renders a html template view and replaces
     * a list of placeholders with given values
     *
     * @param $view
     * @return void
     */
    public function render($view = NULL): void
    {
        if ($this->argv !== NULL && $view === NULL) {
            $view = $this->argv["page"];
        }

        $viewObj = new View($this->VIEW_PATH);

        switch (strtoupper($view)) {
            case 'BASE':
                $viewObj->setTemplate(strtolower($view));
                $placeholders = array(
                    array("%TOOLS_LIST%", $this->renderToolsAsHtml()),
                    array("%PROJECT_NAME%", $this->getProjectName()),
                    array("%PROJECT_VERSION%", $this->getProjectVersion()),
                    array("%PROJECT_AUTHOR%", $this->getProjectAuthor()),
                    array("%PROJECT_DESCRIPTION%", $this->getProjectDescription()),
                    array("%TOOLS_JSON%", $this->getToolsJson())
                );
                $viewObj->setPlaceholders($placeholders);
                break;
            case 'INTEGRATE':
            case 'TEST':
                $viewObj->setTemplate(strtolower($view));
                $placeholders = array(
                    array("%PROJECT_NAME%", $this->getProjectName())
                );
                $viewObj->setPlaceholders($placeholders);
                break;
            default:
                $viewObj->setError(true);
                break;
        }

        View::render($viewObj);
    }

    /**
     * Builds the Runnable and executes it
     */
    public function scan(): void
    {
        if ($this->argv === NULL) {
            die("no arguments provided");
        }

        $engine = (isset($this->argv["engine"])) ? $this->argv["engine"] : NULL;
        $app = (isset($this->argv["index"])) ? $this->argv["index"] : NULL;
        $args = (isset($this->argv["args"])) ? $this->argv["args"] : NULL;
        $id = (isset($this->argv["id"])) ? $this->argv["id"] : NULL;

        if (is_null($engine) || is_null($app) || is_null($args) || is_null($id)) {
            die("invalid arguments or incomplete arg set");
        }

        $runner = (new Scanner())
            ->viaEngine(Engine::fromString($engine))
            ->useCWD($this->APP_PATH)
            ->atPath($app)
            ->withArguments($args)
            ->identifiedBy($id);

        if ($runner->run()) die("done");
        else die("error");

    }

    /**
     * Integrates a new tool to the bundle locally
     */
    public function integrate(): void
    {
        if ($this->argv === NULL) {
            die("no arguments provided");
        }

        $name = (isset($this->argv["name"])) ? $this->argv["name"] : NULL;
        $creator = (isset($this->argv["author"])) ? $this->argv["author"] : NULL;
        $url = (isset($this->argv["url"])) ? $this->argv["url"] : NULL;
        $version = (isset($this->argv["version"])) ? $this->argv["version"] : NULL;
        $cmdline = (isset($this->argv["cmdline"])) ? $this->argv["cmdline"] : NULL;
        $description = (isset($this->argv["description"])) ? $this->argv["description"] : NULL;
        $engine = (isset($this->argv["engine"])) ? $this->argv["engine"] : NULL;
        $index = (isset($this->argv["index"])) ? $this->argv["index"] : NULL;

        if (is_null($name) || is_null($creator) || is_null($url) || is_null($version) || is_null($cmdline)
            || is_null($description) || is_null($engine) || is_null($index) || !isset($_FILES)) {
            die("invalid arguments or incomplete arg set");
        }

        $scanner = (new Scanner())
            ->useCWD($this->TOOLS_PATH)
            ->atPath($index)
            ->viaEngine($engine)
            ->hasName($name)
            ->fromCreator($creator)
            ->setCreatorURL($url)
            ->inVersion($version)
            ->withArguments($cmdline)
            ->describedBy($description)
            ->fileData($_FILES);

        if ($scanner->create()) die("<h1>Scanner Integration successfully finished.</h1>");
        else die("<h1>Something went wrong! Please try again.</h1>");
    }

    /**
     * Deletes an existing tool from the bundle
     */
    public function delete(): void
    {
        if ($this->argv === NULL) {
            die("no arguments provided");
        }

        $id = (isset($this->argv["id"])) ? $this->argv["id"] : NULL;

        if (is_null($id)) {
            die("invalid arguments or incomplete arg set");
        }

        $scanner = (new Scanner())->useCWD($this->TOOLS_PATH)->identifiedBy($id);

        if ($scanner->delete()) die("done");
        else die("error");
    }

    /**
     * Updates an existing tool in the bundle
     */
    public function edit(): void
    {
        $jsonObj = (isset($this->argv["json"])) ? $this->argv["json"] : NULL;

        if (is_null($jsonObj)) {
            die("no json object found");
        }

        $jsonObj = json_decode($jsonObj);

        $id  = (isset($jsonObj->id)) ? $jsonObj->id : NULL;
        $name = (isset($jsonObj->name)) ? $jsonObj->name : NULL;
        $creator = (isset($jsonObj->author)) ? $jsonObj->author : NULL;
        $url = (isset($jsonObj->url)) ? $jsonObj->url : NULL;
        $version = (isset($jsonObj->version)) ? $jsonObj->version : NULL;
        $cmdline = (isset($jsonObj->args)) ? $jsonObj->args : NULL;
        $description = (isset($jsonObj->description)) ? $jsonObj->description : NULL;
        $engine = (isset($jsonObj->engine)) ? $jsonObj->engine : NULL;
        $index = (isset($jsonObj->index)) ? $jsonObj->index : NULL;

        if (is_null($id) || is_null($name) || is_null($creator) || is_null($url) || is_null($version)
            || is_null($cmdline) || is_null($description) || is_null($engine) || is_null($index)) {
            die("invalid arguments or incomplete arg set");
        }

        $scanner = (new Scanner())
            ->useCWD($this->TOOLS_PATH)
            ->atPath($index)
            ->viaEngine($engine)
            ->hasName($name)
            ->fromCreator($creator)
            ->setCreatorURL($url)
            ->inVersion($version)
            ->withArguments($cmdline)
            ->describedBy($description)
            ->identifiedBy($id);

        if ($scanner->update()) die("done");
        else die("error");
    }

    /**
     * Getter for tools object
     *
     * @return array
     */
    private function getToolsObject(): array
    {
        return $this->TOOLS_OBJECT;
    }

    /**
     * Getter for tools object json encoded
     *
     * @return string
     */
    private function getToolsJson(): string
    {
        return json_encode($this->TOOLS_OBJECT);
    }

    /**
     * Getter for project author
     *
     * @return string
     */
    private function getProjectAuthor(): string
    {
        return $this->PROJECT_AUTHOR;
    }

    /**
     * Getter for project name
     *
     * @return string
     */
    private function getProjectName(): string
    {
        return $this->PROJECT_NAME;
    }

    /**
     * Getter for project version
     *
     * @return string
     */
    private function getProjectVersion(): string
    {
        return $this->PROJECT_VERSION;
    }

    /**
     * Getter for project description
     *
     * @return string
     */
    private function getProjectDescription(): string
    {
        return $this->PROJECT_DESCRIPTION;
    }

    /**
     * Renders tools object to html
     *
     * @return string
     */
    private function renderToolsAsHtml(): string
    {
        $html = (count($this->getToolsObject()) === 0) ? "<h2 class='text-muted text-center'>No tools found</h2>" : "";
        foreach ($this->getToolsObject() as $tool) {
            if ($tool->ignore) continue;
            $engine = Engine::fromString($tool->engine);

            $html .= "<div onclick='$(this).toggleClass(`selection`)' id='tool-$tool->id' class=\"list-group-item list-group-item-action tool\" aria-current=\"true\">
            <div class=\"d-flex w-100 justify-content-between\">
                <h5 class=\"mb-1\"><span id=\"title-$tool->id\">$tool->name</span> <span class=\"badge rounded-pill bg-secondary\">$engine</span></h5>
                <small id='state-$tool->id' class='fst-italic'>Idling...</small>
                <div class='hidden' id='options-tool-$tool->id'>
                    <div class=\"d-grid gap-2 d-md-block\">
                      <button onclick='(function(event) {
                          event.stopPropagation();
                          editTool($tool->id)
                      })(event);' class=\"btn btn-sm btn-outline-secondary\" data-bs-toggle=\"modal\" data-bs-target=\"#editModal\" type=\"button\"><i class=\"fa fa-pencil\"></i></button>
                      <button onclick='(function(event) {
                          event.stopPropagation();
                          deleteTool($tool->id)
                      })(event);' class=\"btn btn-sm btn-outline-secondary\" type=\"button\"><i class=\"fa fa-trash\"></i></button>
                    </div>
                </div>
                </div>
                <p id='description-$tool->id' class=\"mb-1\">$tool->description</p>
                <div class=\"d-flex w-100 justify-content-between\">
                    <small>Author: <a href='$tool->url'>$tool->author</a></small>
                    <small id='scanner-$tool->id'>ID: $tool->id</small>
                </div>
            </div>";
        }
        return $html;
    }
}
