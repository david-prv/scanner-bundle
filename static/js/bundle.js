/**
 *  Bundle of all main functions and handlers
 *  this application needs during runtime
 *
 *  Version: 1.0.0
 *  Author: David Dewes <hello@david-dewes.de>
 */

// temp variable declarations
var counter = 0;
var counterS = 0;
var finishedIDs = [];

// code ignition
(function() {
    /*
     * Registration of all necessary
     * EventListeners
     */

    let launchAll = document.getElementById("launchAll");
    launchAll.addEventListener("click", function(event) {
        event.stopImmediatePropagation();
        event.stopPropagation();
        event.preventDefault();
    });

    let launchSelectedOption = document.getElementById("launch-selected");
    launchSelectedOption.addEventListener("click", prepareSelectedModal);

    let launchModal = document.getElementById("launchModal");
    launchModal.addEventListener('hidden.bs.modal', invokeLaunchAll)

    let selectedModal = document.getElementById("selectedModal");
    selectedModal.addEventListener('hidden.bs.modal', invokeLaunchSelected);

    let resultModal = document.getElementById("resModal");
    resultModal.addEventListener('hidden.bs.modal', resetStates);

    $('#launch-all').on("click", () => {
        (new bootstrap.Modal(launchModal, {})).show();
    });

    $('#launch-selected').on("click", () => {
        (new bootstrap.Modal(selectedModal, {})).show();
    });

})();

// handler for tool deletion button
function deleteTool(id) {
    $.get('index.php?delete&id=' + id, function(data) {
        if (data === "done") {
            $('#tool-' + id).remove();
        }
    });
}

// handler for tool edit button
function editTool(id) {
    $('#edit-id').val(id);
    $('#edit-name').val(DATA[id]["name"]);
    $('#edit-creator').val(DATA[id]["author"]);
    $('#edit-url').val(DATA[id]["url"]);
    $('#edit-version').val(DATA[id]["version"]);
    $('#edit-engine').val(DATA[id]["engine"]);
    $('#edit-index').val(DATA[id]["index"]);
    $('#edit-args').val(DATA[id]["args"]);
    $('#edit-description').val(DATA[id]["description"]);
}

// reads values from form and submits them to backend
function submitEdit() {
    let name, creator, url, version, engine, index, args, description, id;

    id = $('#edit-id').val();
    name = $('#edit-name').val();
    creator = $('#edit-creator').val();
    url = $('#edit-url').val();
    version = $('#edit-version').val();
    engine = $('#edit-engine').val();
    index = $('#edit-index').val();
    args = $('#edit-args').val();
    description = $('#edit-description').val();

    let json = {
        "id": id,
        "name": name,
        "author": creator,
        "url": url,
        "version": version,
        "engine": engine,
        "index": index,
        "args": args,
        "description": description
    }

    $.get('index.php?edit&json=' + JSON.stringify(json), function(data) {
        if(data === "done") {
            document.getElementById("edit-result").innerHTML = "<span style='color:green;'>Successfully saved.</span>";
        } else {
            document.getElementById("edit-result").innerHTML = "<span style='color:red;'>Could not be saved.</span>";
        }
    });
}

// reset tool states after finished run
function resetStates() {
    for (let i = 0; i < DATA.length; i++) {
        $(`#state-${i}`)[0].innerText = "Idling...";
    }
}

// show edit tools
function editTools() {
    $('#launchAll, #launchOptions').prop('disabled', (i, v) => !v);
    for (let i = 0; i < DATA.length; i++) {
        $(`#options-tool-${i}`).toggleClass("hidden");
        $(`#state-${i}`).toggleClass("hidden");
    }
}

// prepares the modal for the launchSelected event
function prepareSelectedModal(event) {
    // all selected elements
    let selected = $('.selection');

    // define and clear list
    let list = document.getElementById("selection-list");
    list.innerHTML = "";

    // clean up keys
    delete(selected["length"]);
    delete(selected["prevObject"]);

    // iterate through tools
    let keys = Object.keys(selected);
    if(keys.length === 0) {
        list.innerHTML = "<li style='font-style: italic'>Empty</li>";
        $('#btn-start-selected').attr("disabled", true);
        return;
    }
    $('#btn-start-selected').removeAttr("disabled");
    for (let i = 0; i < keys.length; i++) {
        let tool = selected[keys[i]];

        let newListElement = document.createElement("li");
        newListElement.id = "list-" + $(tool).attr("id");
        newListElement.innerHTML = `<input class=\"form-check-input me-1\" type=\"checkbox\" value=\"${$(tool).attr("id").replace("tool-", "")}\" checked>
                                        ${document.getElementById('title-' + $(tool).attr("id").split("-")[1]).innerText}`;

        list.appendChild(newListElement);
    }
}

// invokes all methods needed for the launchSelected event
function invokeLaunchSelected(event) {
    let queue = [];
    let target = $("#target-url-alt").val();
    if (target === '' || target === null || target === undefined) {
        console.error("[ERROR] Missing target...");
        return;
    }
    if (target.indexOf("://") === -1) target = $("#protocol-alt").val() + "://" + target;

    $("#launchAll").html("<i class=\"fa fa-circle-o-notch fa-spin\"></i> Launching...");

    let inputs = $('#selection-list input');
    let selectedInputs = inputs
        .filter(function(index) { return $(inputs[index]).is(':checked'); });
    selectedInputs = selectedInputs.map(function(index) { return selectedInputs[index].value; });

    for(let i = 0; i < DATA.length; i++) {
        let currentTool = DATA[i];
        if(!Object.values(selectedInputs).includes(currentTool["id"])) { continue; }
        $("#state-" + i).innerText = "Waiting...";
        queue.push("?run&engine=" + currentTool["engine"] + "&index=" + currentTool["index"] + "&args=\""
            + currentTool["args"].replace("%URL%", target) + "\"&id=" + currentTool["id"]);
    }

    for(let j = 0; j < queue.length; j++) {
        $("#state-" + selectedInputs[j]).html("<span class='blinking'>Running...</span>");
        $.get("/index.php" + queue[j], function(data, status, xhr, id=selectedInputs[j], callback=finishedSelected, max=queue.length) {
            $("#state-" + selectedInputs[j]).html("<span style='color:green!important;'>Finished</span>");
            callback(id, selectedInputs);
        });
    }
}

// invokes all methods needed for the launchAll event
function invokeLaunchAll(event) {
    let queue = [];
    let target = $("#target-url").val();
    if (target === '' || target === null || target === undefined) {
        console.error("[ERROR] Missing target...");
        return;
    }
    if (target.indexOf("://") === -1) target = $("#protocol").val() + "://" + target;

    $("#launchAll").html("<i class=\"fa fa-circle-o-notch fa-spin\"></i> Launching...");

    for(let i = 0; i < DATA.length; i++) {
        let currentTool = DATA[i];
        $("#state-" + i).innerText = "Waiting...";
        queue.push("?run&engine=" + currentTool["engine"] + "&index=" + currentTool["index"] + "&args=\"" + currentTool["args"].replace("%URL%", target) + "\"&id=" + currentTool["id"]);
    }

    let skip = [];
    let exclusion = $('#exclusion').val();
    let whitelist = $('#whitelist').val();
    if(exclusion.trim() !== '*' && whitelist.indexOf(",") !== -1) {
        whitelist = whitelist.split(",").map(i => { return parseInt(i); });
    } else {
        whitelist = [parseInt(whitelist)];
    }

    if(exclusion.trim() === "*") {
        for(let i = 0; i < DATA.length; i++) {
            if(!whitelist.includes(i)) skip.push(i);
        }
    } else {
        if(exclusion.indexOf(",") !== -1) {
            skip = exclusion.split(",").map(i => { return parseInt(i); });
        } else {
            if (exclusion !== "" && exclusion !== undefined) {
                skip.push(parseInt(exclusion));
            }
        }
    }

    if(skip.length === queue.length) {
        console.error("[ERROR] All tools skipped...");
        $("#launchAll").html("<i class=\"fa fa-forward\"></i> Launch All");
        return;
    }

    $('#exclusion').val("");
    $('#whitelist').val("");

    for(let j = 0; j < queue.length; j++) {
        if(skip.includes(j)) {
            finished(j, queue.length);
            continue;
        }

        $("#state-" + j).html("<span class='blinking'>Running...</span>");
        $.get("/index.php" + queue[j], function(data, status, xhr, id=j, callback=finished, max=queue.length) {
            $("#state-" + j).html("<span style='color:green!important;'>Finished</span>");
            callback(id, max);
        });
    }
}

// handles the current progress state for launchSelected event
function finishedSelected(index, selected) {
    counterS++;
    console.log("[INFO] Finished task (" + counterS + " / " + selected.length + ")");

    if(counterS === selected.length) {
        let resContent = document.getElementById("result-content");

        let html = "<div class=\"accordion accordion-flush\" id=\"accordion\">";
        for(let i = 0; i < DATA.length; i++) {
            let tool = DATA[i];
            if(!Object.values(selected).includes(tool["id"])) { continue; }

            html += "<div class=\"accordion-item\">" +
                "    <h2 class=\"accordion-header\" id=\"flush-heading-" + i + "\">" +
                "      <button class=\"accordion-button collapsed\" type=\"button\" data-bs-toggle=\"collapse\" data-bs-target=\"#flush-collapse" + i + "\" aria-expanded=\"false\" aria-controls=\"flush-collapse" + i + "\">" +
                "        " + tool["name"] +
                "      </button>" +
                "    </h2>" +
                "    <div id=\"flush-collapse" + i + "\" class=\"accordion-collapse collapse\" aria-labelledby=\"flush-heading" + i + "\" data-bs-parent=\"#accordion\">" +
                "      <div id='body-" + tool["id"] + "' class=\"accordion-body\"></div>" +
                "    </div>" +
                "  </div>";
            finishedIDs.push(tool["id"]);
        }
        html += "</div>";

        console.log(finishedIDs);
        $(resContent).html(html);

        for(let j = 0; j < finishedIDs.length; j++) {
            getText(finishedIDs[j]);
        }

        let resultModal = new bootstrap.Modal(document.getElementById("resModal"), {});
        resultModal.show();
        $("#launchAll").html("<i class=\"fa fa-forward\"></i> Launch All");
        counterS = 0;
        finishedIDs = [];
    }
}

// handles the current progress state
function finished(index, max) {
    counter++;
    console.log("[INFO] Finished task (" + counter + " / " + max + ")");

    if(counter === max) {
        let resContent = document.getElementById("result-content");

        let html = "<div class=\"accordion accordion-flush\" id=\"accordion\">";
        for(let i = 0; i < max; i++) {
            let tool = DATA[i];
            html += "<div class=\"accordion-item\">" +
                "    <h2 class=\"accordion-header\" id=\"flush-heading" + i + "\">" +
                "      <button class=\"accordion-button collapsed\" type=\"button\" data-bs-toggle=\"collapse\" data-bs-target=\"#flush-collapse" + i + "\" aria-expanded=\"false\" aria-controls=\"flush-collapse" + i + "\">" +
                "        " + tool["name"] +
                "      </button>" +
                "    </h2>" +
                "    <div id=\"flush-collapse" + i + "\" class=\"accordion-collapse collapse\" aria-labelledby=\"flush-heading" + i + "\" data-bs-parent=\"#accordion\">" +
                "      <div id='body-" + tool["id"] + "' class=\"accordion-body\"></div>" +
                "    </div>" +
                "  </div>";
        }
        html += "</div>";

        $(resContent).html(html);

        for(let j = 0; j < max; j++) {
            getText(j);
        }

        let resultModal = new bootstrap.Modal(document.getElementById("resModal"), {});
        resultModal.show();
        $("#launchAll").html("<i class=\"fa fa-gears\"></i> Launch All");
        counter = 0;
    }
}

// fetches text from a .txt report
function getText(id) {

    console.log("[INFO] Fetching report", id, 'http://localhost:8080/reports/report_' + id + '.txt');

    // read text from URL location
    var request = new XMLHttpRequest();
    request.open('GET', 'http://localhost:8080/reports/report_' + id + '.txt', true);
    request.send(null);
    request.onreadystatechange = function (event, k=id) {
        if (request.readyState === 4 && request.status === 200) {
            var type = request.getResponseHeader('Content-Type');
            if (type.indexOf("text") !== 1) {
                console.log(request.responseText, k);
                document.getElementById("body-"+k).innerText = request.responseText;
            }
        }
    }
}