(function ($) {
  "use strict";

  $(function () {
    var frame = null;
    var $select = $("#v3da-hero-image-select");
    var $clear = $("#v3da-hero-image-clear");
    var $input = $("#v3da-hero-image-id");
    var $preview = $("#v3da-hero-image-preview");

    if (!$select.length) return;

    $select.on("click", function (e) {
      e.preventDefault();
      if (frame) {
        frame.open();
        return;
      }
      frame = wp.media({
        title: $select.data("title") || "Select Hero Image",
        multiple: false,
        library: { type: "image" },
      });
      frame.on("select", function () {
        var attachment = frame.state().get("selection").first().toJSON();
        $input.val(attachment.id);
        $preview.attr("src", (attachment.sizes && attachment.sizes.thumbnail) ? attachment.sizes.thumbnail.url : attachment.url).show();
        $clear.show();
      });
      frame.open();
    });

    $clear.on("click", function (e) {
      e.preventDefault();
      $input.val("");
      $preview.hide().attr("src", "");
      $clear.hide();
    });
  });
})(jQuery);
