jQuery(document).ready(function ($) {
  let ruleCount = $("#wps-rules-container .wps-rule").length;

  // Add new rule
  $("#wps-add-rule").on("click", function () {
    let template = $("#wps-rule-template").html();
    template = template.replace(/__INDEX__/g, ruleCount);

    $("#wps-rules-container").append(template);

    // Update rule numbers
    updateRuleNumbers();
    ruleCount++;
  });

  // Remove rule
  $(document).on("click", ".wps-remove-rule", function () {
    if (confirm("Are you sure you want to remove this rule?")) {
      $(this).closest(".wps-rule").remove();
      updateRuleNumbers();
    }
  });

  function updateRuleNumbers() {
    $(".wps-rule").each(function (index) {
      $(this)
        .find(".wps-rule-number")
        .text(index + 1);
      // Update the name attribute for the rule
      $(this).attr("data-index", index);
      $(this)
        .find("input, select")
        .each(function () {
          let name = $(this).attr("name");
          if (name) {
            name = name.replace(/\[(\d+)\]/g, "[" + index + "]");
            $(this).attr("name", name);
          }
        });
    });
  }

  // Initialize if no rules exist
  if (ruleCount === 0) {
    $("#wps-add-rule").trigger("click");
  }
});
