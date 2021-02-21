<?php
session_start();


?><html>
    <head>
        <title>img2gco</title>
        <style>
            * {
                font-family: sans-serif;
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
                display: block;
                margin-top: 10px;
                cursor: pointer;
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
            td [type = radio] {
                width: auto;
            }
            img {
                max-width: 200px;
            }
        </style>
        <script src="http://code.jquery.com/jquery-3.5.1.min.js"></script>
        <script>
            function submitParamsAjax(event) {
                var data = $("#parameters").serialize();
                data = data + "&ajax=true";
                console.log(data);
                $("#submit").prop("disabled", true);
                $.ajax({
                    type: "POST",
                    url: "generator.php",
                    data: data,
                    xhrFields: {
                        onprogress: function(e) {
                            lines = e.target.responseText.split(/\r?\n/);
                            $("#generateProgress").text(lines[Math.max(0, lines.length - 2)]);
                        }
                    }, success: function(data) {
                        $("#generateProgress").text("");
                        window.location.href = "index.php"; // php shows download link
                    }, error: function (jqXHR, textStatus, errorThrown) {
                        $("#generateProgress").text("");
                        $("#submit").prop("disabled", false);
                    }
                });
                event.preventDefault();
                return false;
            }
            $().ready(function() {
                var parameters = document.getElementById('parameters');
                parameters.addEventListener('submit', submitParamsAjax);
            });
        </script>
    </head>
    <body>
        <h1>img2gco</h1>
        <?php

        include("lib/upload.php");

        if (isset($_SESSION["filename"])) {
            include("templates/preview.php");
            include("templates/form-params.html");
        } else {
            include("templates/form-upload.html");
        }
        include("templates/about.html");

        ?>
    </body>
</html>
