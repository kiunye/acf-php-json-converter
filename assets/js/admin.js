(() => {
  // assets/js/src/modules/shared.js
  var config = window.fgpjcAdmin || {};
  function debounce(fn, delay) {
    let timer = null;
    return function(...args) {
      window.clearTimeout(timer);
      timer = window.setTimeout(() => fn.apply(this, args), delay);
    };
  }
  function post(action, data) {
    return new Promise((resolve, reject) => {
      window.jQuery.ajax({
        url: config.ajaxUrl,
        method: "POST",
        data: Object.assign(
          {
            action,
            _wpnonce: config.nonce
          },
          data
        )
      }).done(resolve).fail((jqXhr) => reject(jqXhr.responseJSON || { success: false }));
    });
  }

  // assets/js/src/modules/converter.js
  function initConverter($) {
    const $input = $("#fgpjc-input");
    const $output = $("#fgpjc-output");
    const $mode = $('input[name="mode"]');
    const $status = $("#fgpjc-convert-status");
    function announce(message, isError) {
      if (!$status.length) {
        return;
      }
      $status.text(message);
      $status.attr("role", isError ? "alert" : "status");
    }
    function convert() {
      const source = $input.val();
      const mode = $mode.filter(":checked").val();
      if (!source.trim()) {
        $output.val("");
        announce("");
        return;
      }
      post(config.action, { mode, source }).then((response) => {
        if (response.success && response.data) {
          if (response.data.success) {
            $output.val(response.data.output);
            announce("");
          } else if (response.data.errors && response.data.errors.length) {
            $output.val(response.data.errors.join("\n"));
            announce(response.data.errors[0], true);
          }
        }
      }).catch(() => announce("Conversion request failed.", true));
    }
    $input.on("input", debounce(convert, 400));
    $mode.on("change", convert);
    $(".fgpjc-copy").on("click", function() {
      if ($output.val() && window.navigator.clipboard) {
        window.navigator.clipboard.writeText($output.val());
        announce("Output copied to clipboard.");
      }
    });
  }

  // assets/js/src/modules/acf-groups.js
  function download(filename, contents, mime) {
    const blob = new Blob([contents], { type: mime });
    const url = window.URL.createObjectURL(blob);
    const anchor = document.createElement("a");
    anchor.href = url;
    anchor.download = filename;
    anchor.click();
    window.URL.revokeObjectURL(url);
  }
  function initAcfGroups($) {
    const $exportStatus = $("#fgpjc-export-status");
    function announceExport(message, isError) {
      if (!$exportStatus.length) {
        return;
      }
      $exportStatus.text(message);
      $exportStatus.attr("role", isError ? "alert" : "status");
    }
    $("#fgpjc-bulk-export").on("click", function() {
      const $button = $(this);
      $button.prop("disabled", true);
      announceExport("");
      post("fgpjc_bulk_export", {}).then((response) => {
        if (response.success && response.data && response.data.json) {
          download("acf-field-groups.json", response.data.json, "application/json");
          announceExport(
            response.data.count + " field group(s) exported."
          );
        } else {
          announceExport("Export failed.", true);
        }
      }).catch(() => announceExport("Export request failed.", true)).always(() => $button.prop("disabled", false));
    });
    $("#fgpjc-import-file").on("change", function() {
      const file = this.files && this.files[0];
      if (!file) {
        return;
      }
      const reader = new FileReader();
      reader.onload = function() {
        const contents = String(reader.result || "");
        post(config.action, { mode: "json_to_php", source: contents }).then((response) => {
          const $output = $("#fgpjc-output");
          if (response.success && response.data && response.data.success) {
            $output.val(response.data.output);
            announceExport("Imported file converted to PHP.");
          } else if (response.data && response.data.errors) {
            $output.val(response.data.errors.join("\n"));
            announceExport(response.data.errors[0], true);
          }
        }).catch(() => announceExport("Import request failed.", true));
      };
      reader.readAsText(file);
    });
  }

  // assets/js/src/modules/theme.js
  function initTheme($) {
    const $button = $("#fgpjc-scan");
    if (!$button.length) {
      return;
    }
    const $status = $("#fgpjc-scan-status");
    const $results = $("#fgpjc-scan-results");
    const $list = $results.find("ul");
    const $notices = $("#fgpjc-scan-notices");
    function announce(message, isError) {
      $status.text(message);
      $status.attr("role", isError ? "alert" : "status");
    }
    $button.on("click", function() {
      $button.prop("disabled", true);
      announce("");
      $results.prop("hidden", true);
      $list.empty();
      $notices.prop("hidden", true).empty();
      post(config.themeAction, { theme_action: "scan", _wpnonce: config.themeNonce }).then((response) => {
        if (!response.success || !response.data) {
          announce("Scan failed.", true);
          return;
        }
        const groups = response.data.groups || [];
        if (groups.length === 0) {
          announce("No field groups found in the active theme.");
        } else {
          groups.forEach((group) => {
            const label = group.title || group.key || "(unnamed)";
            const source = "json" === group.source ? "Local JSON" : "PHP";
            $list.append(
              $("<li>").text(label + " \u2014 " + source)
            );
          });
          $results.prop("hidden", false);
          announce(groups.length + " field group(s) found.");
        }
        const notices = response.data.notices || [];
        if (notices.length > 0) {
          notices.forEach((note) => {
            $notices.append($("<li>").text(note));
          });
          $notices.prop("hidden", false);
        }
      }).catch(() => announce("Scan request failed.", true)).always(() => $button.prop("disabled", false));
    });
  }

  // assets/js/src/modules/issues.js
  function initIssues($) {
    const $button = $("#fgpjc-check-issues");
    if (!$button.length) {
      return;
    }
    const $status = $("#fgpjc-issues-status");
    const $summary = $("#fgpjc-issues-summary");
    const $list = $("#fgpjc-issues-list");
    function announce(message, isError) {
      $status.text(message);
      $status.attr("role", isError ? "alert" : "status");
    }
    $button.on("click", function() {
      $button.prop("disabled", true);
      announce("");
      $list.empty();
      $summary.prop("hidden", true).empty();
      const source = $("#fgpjc-input").val();
      const isJson = /^\s*[[{]/.test(source);
      const format = isJson ? "json" : "php";
      post(config.issuesAction, { source, format, _wpnonce: config.issuesNonce }).then((response) => {
        if (!response.success || !response.data) {
          announce("Issue check failed.", true);
          return;
        }
        const data = response.data;
        const issues = data.issues || [];
        const errors = data.errors || 0;
        const warnings = data.warnings || 0;
        if (0 === issues.length) {
          announce("No issues were detected.");
          return;
        }
        $summary.prop("hidden", false).text(
          errors + " error(s), " + warnings + " warning(s)"
        );
        issues.forEach((issue) => {
          const $item = $("<li>").addClass("fgpjc-issue fgpjc-issue-" + issue.severity).text(issue.message);
          if (issue.context) {
            $item.append($("<span>").addClass("fgpjc-issue-context").text(" (" + issue.context + ")"));
          }
          $list.append($item);
        });
        announce(errors + " error(s) and " + warnings + " warning(s) found.");
      }).catch(() => announce("Issue check request failed.", true)).always(() => $button.prop("disabled", false));
    });
  }

  // assets/js/src/admin.js
  function initTabs($) {
    const $tabs = $(".fgpjc-tab");
    if (!$tabs.length) {
      return;
    }
    $tabs.on("click", function() {
      const $tab = $(this);
      const target = $tab.attr("aria-controls");
      $tabs.removeClass("is-active").attr("aria-selected", "false");
      $tab.addClass("is-active").attr("aria-selected", "true");
      $(".fgpjc-panel").prop("hidden", true);
      $("#" + target).prop("hidden", false);
    });
  }
  window.jQuery(function($) {
    initTabs($);
    initConverter($);
    initAcfGroups($);
    initTheme($);
    initIssues($);
  });
})();
