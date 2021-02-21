<?php
session_start();
include("lib/upload.php");
?><html>
    <head>
        <title>img2gco</title>
        <style>
            * {
                font-family: sans-serif;
                box-sizing: border-box;
            }
            body {
                max-width: 1000px;
                margin: auto;
                padding: 10px 0px;
            }
            /* Title */
            table.params td:nth-child(1) {
                font-weight: bold;
                width: 300px;
            }
            /* Explanation */
            table.params td:nth-child(3) {
                color: #777;
                font-size: smaller;
            }
            td {
                padding: 2px;
                margin: 0px;
            }
            table.params tr:nth-child(odd) {
                background: #f2f2f2;
            }
            table {
                border-spacing: 0px;
            }
            p {
                background: #f2f2f2;
                padding: 5px;
                margin-bottom: 5px;
            }
            input[type = submit], .button {
                color: #fff;
                background: #cc3300;
                border: none;
                padding: 10px 50px;
                margin: auto;
                margin-top: 5px;
                display: block;
                cursor: pointer;
                text-decoration: none;
                text-align: center;
            }
            input[type = submit]:hover, .button:hover {
                background: #990000;
            }
            input[type = submit]:disabled {
                background: #ccc;
                cursor: default;
            }
            td input {
                width: 200px;
            }
            td input.half {
                width: 75px;
            }
            td span.halfLabel {
                margin-left: 5px;
                width: 18px;
                display: inline-block;
            }
            td [type = radio] {
                width: auto;
            }
            img {
                height: 300px;
            }
            #generateProgress {
                margin: 10px auto;
                display: none;
                text-align: center;
                background: #ffcccc;
                padding: 10px;
                width: 100px;
            }
        </style>
        <script src="templates/jquery-3.5.1.min.js"></script>
        <script>
            var previewValid = true;
            function refreshPreview() {
                $.ajax({
                    url: "preview.php",
                    success: function(data) {
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
                    success: function(data) {
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
                        onprogress: function(e) {
                            lines = e.target.responseText.split(/\r?\n/);
                            $("#generateProgress").text(lines[Math.max(0, lines.length - 2)]);
                        }
                    }, success: function(data) {
                        $("#generateProgress").text("");
                        $("#generateProgress").hide(200);
                        refreshPreview();
                    }, error: function (jqXHR, textStatus, errorThrown) {
                        $("#generateProgress").text("");
                        $("#generateProgress").hide(200);
                        refreshPreview();
                    }
                });
            }
            $().ready(function () {
                $("input").change(function() {
                    invalidate();
                });
                $("#sizeX").change(function() {
                    $("#sizeY").val(Math.round($("#sizeX").val() / whRatio));
                    invalidate();
                });
                $("#sizeY").change(function() {
                    $("#sizeX").val(Math.round($("#sizeY").val() * whRatio));
                    invalidate();
                });
                $("#sizeX").val(Math.round($("#sizeY").val() * whRatio));
            });
            <?php
            if (isset($_SESSION["filename"])) {
                $sourceImagePath = $_SESSION["filename"] . "." . $_SESSION["ext"];
                list($w, $h) = getimagesize($sourceImagePath);
                echo "var whRatio = " . ($w/$h);
            }
            ?>
        </script>
    </head>
    <body>
        <h1>img2gco</h1>
        <div id="generateProgress"></div>
        <?php

        if (isset($_SESSION["filename"])) {
            echo "<div id='preview'>";
            include("preview.php");
            echo "</div>";
            include("templates/form-params.html");
        } else {
            include("templates/form-upload.html");
            include("templates/about.html");
        }

        ?>
    </body>
</html>
