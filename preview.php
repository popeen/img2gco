<?php
@session_start();
?>
<table>
    <tr>
        <td>Input image</td>
        <td>Preview</td>
        <td>Gcode</td>
    </tr>
    <tr>
        <td>
            <img src="<?php echo $_SESSION["filename"] . "." . $_SESSION["ext"] ?>" style="background:#222;" />
        </td>
        <td>
            <?php
            if (file_exists($_SESSION["filename"] . ".svg")) {
                $timestamp = filemtime($_SESSION["filename"] . ".svg");
                echo "<img class='invalidate-on-param-change' src='" . $_SESSION["filename"] . ".svg?refresh=$timestamp' />";
            }
            ?>
            <a href='javascript:generate("svg")' class="button">Generate preview</a>
        </td>
        <td>
            <?php
            if (file_exists($_SESSION["filename"] . ".gcode")) {
                echo "<a class='button invalidate-on-param-change' href='" . $_SESSION["filename"] . ".gcode' target='_blank' download>✓ Download gcode</a>";
            } else {
                echo "No gcode generated yet.";
            }
            ?>
            <br />
            <br />
            <a href='javascript:generate("reprap")' class="button">Generate reprap gcode</a>
            <a href='javascript:generate("grbl")' class="button">Generate grbl gcode</a>
            <a href='index.php?do=clearImage' class="button">✗ Select other image</a>
        </td>
    </tr>
</table>
<br/>
<br/>
