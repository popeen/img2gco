<?php
session_start();
include("lib/upload.php");
?><html>
    <head>
        <title>img2gco</title>
        <link rel="stylesheet" href="templates/style.css" />
        <script src="templates/jquery-3.5.1.min.js"></script>
        <script src="templates/main.js"></script>
        <script>
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
