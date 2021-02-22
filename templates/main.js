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
var expiry = new Date((new Date()).getTime() + 356 * 24 * 3600 * 1000); // 1 year
function deleteCookie(name) {
    document.cookie = name + "=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GM";
}
function setCookie(name, value) {
    document.cookie = name + "=" + escape(value) + "; path=/; expires=" + expiry.toGMTString();
}
function getCookie(name) {
    var cookieArr = document.cookie.split(";");
    for (var i = 0; i < cookieArr.length; i++) {
        var cookiePair = cookieArr[i].split("=");
        if (name === cookiePair[0].trim()) {
            return decodeURIComponent(cookiePair[1]);
        }
    }
    return null;
}
function saveParametersInCookie() {
    $("input").each(function () {
        setCookie("img2gco_autofill_" + $(this).attr("name"), $(this).val());
    });
}
function restoreParametersFromCookie() {
    $("input").each(function () {
        $content = getCookie("img2gco_autofill_" + $(this).attr("name"));
        if ($content !== null) {
            $(this).val($content);
        }
    });
}
function restoreDefaultParameters() {
    $("input").each(function () {
        deleteCookie("img2gco_autofill_" + $(this).attr("name"));
    });
    window.location.href = "index.php";
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
        saveParametersInCookie();
        invalidate();
    });
    $("#sizeX").change(function () {
        $("#sizeY").val(Math.round($("#sizeX").val() / whRatio));
        saveParametersInCookie();
        invalidate();
    });
    $("#sizeY").change(function () {
        $("#sizeX").val(Math.round($("#sizeY").val() * whRatio));
        saveParametersInCookie();
        invalidate();
    });
    restoreParametersFromCookie();
    $("#sizeX").val(Math.round($("#sizeY").val() * whRatio));
});
