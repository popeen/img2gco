<table>
    <tr>
        <td>Input image</td>
        <td>&nbsp;</td>
    </tr>
    <tr>
        <td>
            <img src="<?php echo $_SESSION["filename"] . "." . $_SESSION["ext"] ?>" />
        </td>
        <td>
            <?php
            if (file_exists($_SESSION["filename"] . ".gcode")) {
                echo "<a class='button' href='" . $_SESSION["filename"] . ".gcode' target='_blank' download>Download gcode</a>";
            }
            ?>
        </td>
        <td>
            <a href='index.php?do=clearImage' class="button">Select another image</a>
        </td>
    </tr>
</table>
<br/>
<br/>