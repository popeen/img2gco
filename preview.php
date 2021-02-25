<?php
@session_start();
?>
<table>
    <tr id="headings">
        <td>Input image</td>
        <td>Preview</td>
        <td>Gcode</td>
    </tr>
    <tr id="previews">
        <td>
            <img src="<?php echo $_SESSION["filename"] . "." . $_SESSION["ext"] ?>" />
        </td>
        <td>
            <?php
            if (file_exists($_SESSION["filename"] . ".svg")) {
                $timestamp = filemtime($_SESSION["filename"] . ".svg");
                echo "<img class='invalidate-on-param-change' src='" . $_SESSION["filename"] . ".svg?refresh=$timestamp' />";
            }
            ?>
        </td>
        <td>
            <?php
            if (file_exists($_SESSION["filename"] . ".ngc")) {
                echo "<a class='button invalidate-on-param-change' href='" . $_SESSION["filename"] . ".ngc' target='_blank' download>✓ Download gcode</a>";
            } else {
                echo "No gcode generated yet.";
            }
            ?>
        </td>
    </tr>
    <tr id="buttons">
        <td>
            <a href='index.php?do=rotate' class="button">↴ Rotate</a>
            <a href='index.php?do=clearImage' class="button">✗ Select other image</a>
        </td>
        <td>
            <a href='javascript:generate("svg")' class="button">⚙ Generate preview</a>
        </td>
        <td>
            <a href='javascript:generate("reprap")' class="button">⚙ Generate reprap gcode</a>
            <a href='javascript:generate("grbl")' class="button">⚙ Generate grbl gcode</a>
        </td>
    </tr>
</table>
<br/>
<br/>
