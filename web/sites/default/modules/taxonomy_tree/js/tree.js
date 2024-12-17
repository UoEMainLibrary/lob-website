function getTerms(termId) {
  return new Promise((resolve, reject) => {

    // var baseUrl = drupalSettings.path.baseUrl;
    // var baseUrl = Drupal.url();

    // var url = baseUrl + "/api/v1/taxonomy/concepts/";
    var url = "/api/v1/taxonomy/concepts/";

    if (termId) {
      url += termId;
    }

    var request = new XMLHttpRequest();
    request.onreadystatechange = function () {
      if (request.readyState == XMLHttpRequest.DONE) {
        if (request.status == 200) {
          resolve(JSON.parse(request.responseText));
        } else {
          reject();
        }
      }
    };

    request.open("GET", url, true);
    request.send();
  });
}

function toggleTerms(elementId, termId) {
  var element = document.getElementById(elementId);

  if (!element || !element.classList.contains("expanded")) {
    insertTerms(elementId, termId);
  } else {
    removeTerms(element);
  }
}

function removeTerms(element) {
  var children = document.querySelector(`li[id="${element.id}"] > .term-list`);

  if (children) {
    children.remove();
  }

  element.classList.remove("expanded");
  element.getElementsByClassName("term-toggle")[0].innerHTML = "+";
}

function insertTerms(elementId, termId) {
  var element = document.getElementById(elementId);

  if (element) {
    element.classList.add("expanded");
    if (element.getElementsByClassName("term-toggle")[0]) {
      element.getElementsByClassName("term-toggle")[0].innerHTML = "-";
    }

    if (!termId) {
      // Clear the container for the root terms.
      element.innerHTML = "";
    }

    var loading = document.createElement("term-loading");
    loading.classList.add("term-loading-spinner");
    loading.innerText = "Loading..";

    element.appendChild(loading);

    getTerms(termId).then((result) => {
      loading.remove();

      if(result.length) {
        element.classList.remove("empty");

        var ul = document.createElement("ul");
        ul.classList.add("term-list");
        ul.setAttribute("id", termId ? termId : "root");

        result.forEach((t) => {
          var toggle = document.createElement("a");
          toggle.classList.add("term-toggle");
          toggle.setAttribute("href", `javascript:toggleTerms(${t.id}, ${t.id})`);
          toggle.text = "+";

          if (t.children_count == 0) {
            toggle.classList.add("hidden");
          }

          var label = document.createElement("a");
          label.classList.add("term-label");
          label.setAttribute("href", t.url);
          label.innerText = t.label;
          toggle.setAttribute("title","Toggle narrow concepts of " + t.label)

          var count = document.createElement("span");
          count.classList.add("term-children-count");
          count.classList.add(`n-${t.children_count}`);
          count.innerText = t.children_count;

          var wrapper = document.createElement("div");
          wrapper.classList.add("term-list-item-wrapper");
          wrapper.appendChild(toggle);
          wrapper.appendChild(label);
          wrapper.appendChild(count);

          var li = document.createElement("li");
          li.setAttribute("id", t.id);
          li.classList.add("term-list-item");
          li.appendChild(wrapper);

          ul.appendChild(li);

          element.appendChild(ul);
        });
      } else {
        element.classList.add("empty");

        var msg = document.createElement("span");
        msg.classList.add("term-list-empty-msg");
        msg.innerText = "No items.";

        element.appendChild(msg);
      }
    });
  }
}
