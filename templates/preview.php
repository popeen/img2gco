<div id="generateProgress"></div>
<table>
    <tr>
        <td>Input image</td>
        <td>Preview</td>
        <td>Gcode</td>
    </tr>
    <tr>
        <td>
            <img src="<?php echo $_SESSION["filename"] . "." . $_SESSION["ext"] ?>" />
        </td>
        <td>
            <?php
            if (file_exists($_SESSION["filename"] . ".svg")) {
                echo "<img src='" . $_SESSION["filename"] . ".svg?refresh=". (new DateTime())->getTimestamp() . "' />";
            }
            ?>
            <a href='javascript:generate("svg")' class="button">Generate preview</a>
        </td>
        <td>
            <?php
            if (file_exists($_SESSION["filename"] . ".gcode")) {
                echo "<a class='button' href='" . $_SESSION["filename"] . ".gcode' target='_blank' download>Download gcode</a>";
            } else {
                echo "No gcode generated yet.";
            }
            ?>
            <a href='javascript:generate("reprap")' class="button">Generate reprap gcode</a>
            <a href='javascript:generate("grbl")' class="button">Generate grbl gcode</a>
            <a href='index.php?do=clearImage' class="button">Select another image</a>
        </td>
    </tr>
</table>
<br/>
<br/>