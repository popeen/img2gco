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
                $sourceImagePath = $_SESSION["filename"] . ".png";
                list($w, $h) = getimagesize($sourceImagePath);
                $w = max(1, $w);
                $h = max(1, $h);
                echo "var whRatio = " . ($w/$h);
            }
            ?>
        </script>
    </head>
    <body>
        <div id="generateProgress"></div>
        <h1>img2gco</h1>
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
