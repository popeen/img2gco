var previewValid = true;
function refreshPreview() {
    $.ajax({
        url: "preview.php",
        success: function (data) {
            $("#preview").html(data);
            previewValid = true;
        }
    });
}
function invalidate() {
    if (!previewValid) {
        return;
    }
    $(".invalidate-on-param-change").css("opacity", "0.1");
    $.ajax({
        url: "index.php?do=invalidate",
        success: function (data) {
            $(".invalidate-on-param-change").css("opacity", "0.1");
            previewValid = false;
        }
    });
}
function generate(code) {
    var data = $("#parameters").serialize();
    $("#submit").prop("disabled", true);
    $("#generateProgress").show(200);
    $.ajax({
        type: "POST",
        url: "generator.php",
        data: data + "&code=" + code,
        xhrFields: {
            onprogress: function (e) {
                lines = e.target.responseText.split(/\r?\n/);
                $("#generateProgress").text(lines[Math.max(0, lines.length - 2)]);
            }
        }, success: function (data) {
            $("#generateProgress").text("100%");
            $("#generateProgress").delay(1000).hide(200).queue(function () {
                $("#generateProgress").text("");
                $(this).dequeue();
            });
            refreshPreview();
        }, error: function (jqXHR, textStatus, errorThrown) {
            $("#generateProgress").text("");
            $("#generateProgress").hide(200);
            refreshPreview();
        }
    });
}
// whRatio is defined in php
whRatio = 0;
$().ready(function () {
    $("input").change(function () {
        invalidate();
    });
    $("#sizeX").change(function () {
        $("#sizeY").val(Math.round($("#sizeX").val() / whRatio));
        invalidate();
    });
    $("#sizeY").change(function () {
        $("#sizeX").val(Math.round($("#sizeY").val() * whRatio));
        invalidate();
    });
    $("#sizeX").val(Math.round($("#sizeY").val() * whRatio));
});
