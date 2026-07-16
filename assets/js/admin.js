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

  // assets/js/src/admin.js
  window.jQuery(function($) {
    initConverter($);
    initAcfGroups($);
  });
})();
